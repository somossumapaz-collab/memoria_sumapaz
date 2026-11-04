<?php
/**
 * Auto-corrección y sincronización por coincidencia igual de nombre en resultado_concurso_AMBIENTAL:
 * 1. Si `resultados_json` incluye `participantName` (o `nombre_productor` + `apellido_productor`),
 *    verifica por coincidencia igual de nombre que `id_productor_predio` apunte exactamente a ese productor.
 * 2. Si proviene de una APK anterior sin `participantName` y cayó en colisión de `local_id`
 *    o `participantId`, resuelve el productor real por inscripción + coordenadas y escribe
 *    `participantName`, `nombre_productor`, `apellido_productor` y `numero_documento` dentro de `resultados_json`.
 */
if (!function_exists('healAmbientalEvaluations')) {
    function healAmbientalEvaluations(PDO $pdo): void {
        try {
            $normName = function ($s): string {
                $s = strtolower(trim((string)$s));
                $s = strtr($s, [
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
                    'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n'
                ]);
                $s = preg_replace('/\s+/', ' ', $s);
                return trim($s);
            };

            // 1. Cargar productores en orden de ID
            $prodsStmt = $pdo->query("SELECT id, local_id, nombre_productor, apellido_productor, numero_documento, vereda, nombre_predio, latitud, longitud FROM productor_predio_AMBIENTAL ORDER BY id ASC");
            $seq = 1;
            $seqToProdId = [];
            $prodMap = [];
            $exactNameBuckets = [];
            $docBuckets = [];
            $coordBuckets = [];

            while ($p = $prodsStmt->fetch(PDO::FETCH_ASSOC)) {
                $pid = (int)$p['id'];
                $nom = trim((string)($p['nombre_productor'] ?? ''));
                $ape = trim((string)($p['apellido_productor'] ?? ''));
                $full = trim($nom . ' ' . $ape);
                $nFull = $normName($full);
                $doc = trim((string)($p['numero_documento'] ?? ''));

                $seqToProdId[$seq] = $pid;
                $prodMap[$pid] = [
                    'id' => $pid,
                    'local_id' => $p['local_id'] !== null ? (int)$p['local_id'] : 0,
                    'nombre' => $nom,
                    'apellido' => $ape,
                    'full' => $full,
                    'norm_full' => $nFull,
                    'doc' => $doc,
                    'vereda' => trim((string)($p['vereda'] ?? '')),
                    'predio' => trim((string)($p['nombre_predio'] ?? '')),
                    'lat' => $p['latitud'] !== null ? (float)$p['latitud'] : null,
                    'lon' => $p['longitud'] !== null ? (float)$p['longitud'] : null,
                    'seq' => $seq,
                ];
                if ($nFull !== '') {
                    $exactNameBuckets[$nFull][] = $pid;
                }
                if ($doc !== '') {
                    $docBuckets[$doc][] = $pid;
                }
                if ($p['latitud'] !== null && $p['longitud'] !== null) {
                    $key = round((float)$p['latitud'], 4) . ',' . round((float)$p['longitud'], 4);
                    $coordBuckets[$key][] = $pid;
                }
                $seq++;
            }

            // 2. Cargar mapa de inscripciones por productor
            $inscMap = [];
            $inscStmt = $pdo->query("SELECT id_productor_predio, id_concurso FROM inscripcion_concurso_AMBIENTAL");
            while ($r = $inscStmt->fetch(PDO::FETCH_ASSOC)) {
                $inscMap[(int)$r['id_productor_predio']][(int)$r['id_concurso']] = true;
            }

            // 3. Revisar evaluaciones en resultado_concurso_AMBIENTAL
            $evalsStmt = $pdo->query("SELECT id, id_productor_predio, id_concurso, coordenadas, resultados_json FROM resultado_concurso_AMBIENTAL ORDER BY id ASC");
            $evals = $evalsStmt->fetchAll(PDO::FETCH_ASSOC);

            $updBothStmt = $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET id_productor_predio = ?, resultados_json = ? WHERE id = ?");
            $chkDupStmt = $pdo->prepare("SELECT id FROM resultado_concurso_AMBIENTAL WHERE id_productor_predio = ? AND id_concurso = ? AND id != ? LIMIT 1");
            $delDupStmt = $pdo->prepare("DELETE FROM resultado_concurso_AMBIENTAL WHERE id = ?");

            foreach ($evals as $ev) {
                $evalId = (int)$ev['id'];
                $curPid = (int)$ev['id_productor_predio'];
                $cid = (int)$ev['id_concurso'];
                if ($cid <= 0) continue;

                $json = json_decode($ev['resultados_json'] ?? '', true);
                if (!is_array($json)) $json = [];

                $curProd = $prodMap[$curPid] ?? null;
                $candidatePid = 0;

                // Prioridad 1: Si el JSON trae el nombre del participante desde el aplicativo (`participantName` o `nombre_productor` + `apellido_productor`), hacer coincidencia igual por nombre
                $jsonFullName = trim((string)($json['participantName'] ?? ''));
                if ($jsonFullName === '' && (!empty($json['nombre_productor']) || !empty($json['apellido_productor']))) {
                    $jsonFullName = trim(($json['nombre_productor'] ?? '') . ' ' . ($json['apellido_productor'] ?? ''));
                }
                $nJsonName = $normName($jsonFullName);

                if ($nJsonName !== '') {
                    if ($curProd && $curProd['norm_full'] === $nJsonName) {
                        $candidatePid = $curPid;
                    } elseif (!empty($exactNameBuckets[$nJsonName])) {
                        // Si hay varios registros con el mismo nombre exacto, priorizar el que esté inscrito en el concurso
                        $cands = $exactNameBuckets[$nJsonName];
                        $chosen = $cands[count($cands) - 1];
                        foreach ($cands as $cId) {
                            if (isset($inscMap[$cId][$cid])) {
                                $chosen = $cId;
                                break;
                            }
                        }
                        $candidatePid = $chosen;
                    }
                }

                // Prioridad 2: Si el JSON no traía nombre (o aún no se resolvió), verificar inscripción y coordenadas para detectar colisión de local_id
                if ($candidatePid <= 0) {
                    $isInsc = isset($inscMap[$curPid][$cid]);
                    $evalLat = null;
                    $evalLon = null;
                    $rawCoords = trim((string)($ev['coordenadas'] ?? ''));
                    if ($rawCoords !== '' && $rawCoords !== 'N/A' && strpos($rawCoords, ',') !== false) {
                        $parts = explode(',', $rawCoords);
                        if (count($parts) >= 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                            $evalLat = (float)trim($parts[0]);
                            $evalLon = (float)trim($parts[1]);
                        }
                    }

                    $sameCoords = false;
                    if ($curProd && $curProd['lat'] !== null && $curProd['lon'] !== null && $evalLat !== null && $evalLon !== null) {
                        if (abs($curProd['lat'] - $evalLat) < 0.0002 && abs($curProd['lon'] - $evalLon) < 0.0002) {
                            $sameCoords = true;
                        }
                    }

                    if ($isInsc && ($sameCoords || $evalLat === null)) {
                        $candidatePid = $curPid;
                    } else {
                        if ($curProd && $curProd['local_id'] > 0 && isset($seqToProdId[$curProd['local_id']])) {
                            $seqPid = $seqToProdId[$curProd['local_id']];
                            $seqProd = $prodMap[$seqPid] ?? null;
                            if ($seqProd && isset($inscMap[$seqPid][$cid])) {
                                if ($evalLat === null || ($seqProd['lat'] !== null && abs($seqProd['lat'] - $evalLat) < 0.0002 && abs($seqProd['lon'] - $evalLon) < 0.0002)) {
                                    $candidatePid = $seqPid;
                                }
                            }
                        }
                        if ($candidatePid <= 0 && $evalLat !== null && $evalLon !== null && !$sameCoords) {
                            $bKey = round($evalLat, 4) . ',' . round($evalLon, 4);
                            $matches = [];
                            foreach ($coordBuckets[$bKey] ?? [] as $candId) {
                                if (isset($inscMap[$candId][$cid])) {
                                    $matches[] = $candId;
                                }
                            }
                            if (count($matches) === 1) {
                                $candidatePid = $matches[0];
                            }
                        }
                        if ($candidatePid <= 0 && !$isInsc && $evalLat === null && isset($seqToProdId[$curPid])) {
                            $seqPid = $seqToProdId[$curPid];
                            if (isset($inscMap[$seqPid][$cid])) {
                                $candidatePid = $seqPid;
                            }
                        }
                    }
                }

                $finalPid = $candidatePid > 0 ? $candidatePid : $curPid;
                $finalProd = $prodMap[$finalPid] ?? null;

                // Asegurar que resultados_json siempre tenga el nombre exacto del productor evaluado
                $jsonNeedsUpdate = false;
                if ($finalProd && $finalProd['full'] !== '') {
                    if (($json['participantName'] ?? '') !== $finalProd['full'] || ($json['id_productor_predio'] ?? 0) !== $finalPid) {
                        $json['id_productor_predio'] = $finalPid;
                        $json['participantName'] = $finalProd['full'];
                        $json['nombre_productor'] = $finalProd['nombre'];
                        $json['apellido_productor'] = $finalProd['apellido'];
                        if ($finalProd['doc'] !== '') $json['numero_documento'] = $finalProd['doc'];
                        if ($finalProd['vereda'] !== '') $json['vereda'] = $finalProd['vereda'];
                        if ($finalProd['predio'] !== '') $json['nombre_predio'] = $finalProd['predio'];
                        $jsonNeedsUpdate = true;
                    }
                }

                if ($finalPid !== $curPid) {
                    $chkDupStmt->execute([$finalPid, $cid, $evalId]);
                    $existingDup = $chkDupStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$existingDup) {
                        $newJsonStr = json_encode($json, JSON_UNESCAPED_UNICODE);
                        $updBothStmt->execute([$finalPid, $newJsonStr, $evalId]);
                    }
                } elseif ($jsonNeedsUpdate) {
                    $newJsonStr = json_encode($json, JSON_UNESCAPED_UNICODE);
                    $updBothStmt->execute([$finalPid, $newJsonStr, $evalId]);
                }
            }
        } catch (Throwable $e) {
            // Silencioso para no interrumpir la respuesta de los endpoints
        }
    }
}
