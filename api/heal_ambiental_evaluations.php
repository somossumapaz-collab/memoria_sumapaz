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

            // 4. Completar únicamente firmas faltantes en resultado_concurso_AMBIENTAL sin alterar ningún otro dato:
            //    - firma_evaluador faltante: se toma de otra evaluación del mismo evaluador que sí tenga firma válida (data:image/).
            //    - firma_concursante faltante: se toma del acta de inscripción del participante (productor_predio_AMBIENTAL.firma_productor).
            $normJudgeKey = function ($s) use ($normName): string {
                $s = $normName($s);
                $s = preg_replace('/[^a-z\s]/', ' ', $s);
                $s = trim(preg_replace('/\s+/', ' ', $s));
                if ($s === '') return '';
                if (strpos($s, 'yeison') !== false || strpos($s, 'yeiosn') !== false) return 'yeison paez';
                if (strpos($s, 'laura') !== false && strpos($s, 'bolivar') !== false) return 'laura yaneth bolivar hoyos';
                if (strpos($s, 'edward') !== false && strpos($s, 'barrera') !== false) return 'edward barrera';
                if (strpos($s, 'esthefanny') !== false && strpos($s, 'romero') !== false) return 'esthefanny romero';
                if (strpos($s, 'libardo') !== false && strpos($s, 'diaz') !== false) return 'libardo diaz';
                if (strpos($s, 'juan david nieto') !== false) return 'juan david nieto beltran';
                if (strpos($s, 'lizeth') !== false && strpos($s, 'sua') !== false) return 'lizeth suarez';
                if (strpos($s, 'milena') !== false && strpos($s, 'su') !== false) return 'milena susa';
                if (strpos($s, 'sergio') !== false && strpos($s, 'parra') !== false) return 'sergio parra';
                if (strpos($s, 'daniel') !== false && strpos($s, 'millan') !== false) return 'daniel millan';
                if ((strpos($s, 'smith') !== false || strpos($s, 'smirh') !== false) && strpos($s, 'villalobos') !== false) return 'smith villalobos';
                if (strpos($s, 'wilson') !== false && strpos($s, 'rey') !== false) return 'wilson rey moreno';
                if (strpos($s, 'luis fernando baquero') !== false) return 'luis fernando baquero';
                if (strpos($s, 'jair alonso') !== false) return 'jair alonso';
                if (strpos($s, 'eduin') !== false && strpos($s, 'parada') !== false) return 'eduin parada';
                return $s;
            };

            $sigMetaStmt = $pdo->query("
                SELECT id, id_productor_predio, resultados_json,
                       (firma_evaluador IS NOT NULL AND firma_evaluador LIKE '%data:image/%' AND LENGTH(firma_evaluador) > 100) AS has_fe,
                       (firma_concursante IS NOT NULL AND firma_concursante LIKE '%data:image/%' AND LENGTH(firma_concursante) > 100) AS has_fc
                FROM resultado_concurso_AMBIENTAL
                ORDER BY id ASC
            ");
            $sigMetaRows = $sigMetaStmt->fetchAll(PDO::FETCH_ASSOC);

            $donorEvalIdByJudge = [];
            $donorEvalIdByParticipant = [];
            $missingRows = [];

            foreach ($sigMetaRows as $sm) {
                $eid = (int)$sm['id'];
                $pid = (int)$sm['id_productor_predio'];
                $hasFe = (int)$sm['has_fe'] === 1;
                $hasFc = (int)$sm['has_fc'] === 1;
                $j = json_decode($sm['resultados_json'] ?? '', true);
                $jName = (is_array($j) && !empty($j['judgeName'])) ? trim((string)$j['judgeName']) : '';
                $jk = $normJudgeKey($jName);

                if ($hasFe && $jk !== '' && !isset($donorEvalIdByJudge[$jk])) {
                    $donorEvalIdByJudge[$jk] = $eid;
                }
                if ($hasFc && $pid > 0 && !isset($donorEvalIdByParticipant[$pid])) {
                    $donorEvalIdByParticipant[$pid] = $eid;
                }
                if (!$hasFe || !$hasFc) {
                    $missingRows[] = [
                        'id' => $eid,
                        'pid' => $pid,
                        'jk' => $jk,
                        'has_fe' => $hasFe,
                        'has_fc' => $hasFc,
                    ];
                }
            }

            if (!empty($missingRows)) {
                $judgeSigCache = [];
                $getJudgeFeStmt = $pdo->prepare("SELECT firma_evaluador FROM resultado_concurso_AMBIENTAL WHERE id = ? LIMIT 1");
                $getProdFpByIdStmt = $pdo->prepare("SELECT firma_productor FROM productor_predio_AMBIENTAL WHERE id = ? AND firma_productor LIKE '%data:image/%' AND LENGTH(firma_productor) > 100 LIMIT 1");
                $getProdFpByDocStmt = $pdo->prepare("SELECT firma_productor FROM productor_predio_AMBIENTAL WHERE numero_documento = ? AND firma_productor LIKE '%data:image/%' AND LENGTH(firma_productor) > 100 ORDER BY id DESC LIMIT 1");
                $getEvalFcByIdStmt = $pdo->prepare("SELECT firma_concursante FROM resultado_concurso_AMBIENTAL WHERE id = ? LIMIT 1");
                $updFeStmt = $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET firma_evaluador = ? WHERE id = ?");
                $updFcStmt = $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET firma_concursante = ? WHERE id = ?");

                foreach ($missingRows as $mr) {
                    $eid = $mr['id'];
                    $pid = $mr['pid'];
                    $jk = $mr['jk'];

                    if (!$mr['has_fe'] && $jk !== '' && isset($donorEvalIdByJudge[$jk])) {
                        if (!array_key_exists($jk, $judgeSigCache)) {
                            $getJudgeFeStmt->execute([$donorEvalIdByJudge[$jk]]);
                            $rawFe = $getJudgeFeStmt->fetchColumn();
                            $judgeSigCache[$jk] = (is_string($rawFe) && strpos($rawFe, 'data:image/') !== false) ? $rawFe : null;
                        }
                        if (!empty($judgeSigCache[$jk])) {
                            $updFeStmt->execute([$judgeSigCache[$jk], $eid]);
                        }
                    }

                    if (!$mr['has_fc'] && $pid > 0) {
                        $foundFc = null;
                        $getProdFpByIdStmt->execute([$pid]);
                        $rawFp = $getProdFpByIdStmt->fetchColumn();
                        if (is_string($rawFp) && strpos($rawFp, 'data:image/') !== false) {
                            $foundFc = $rawFp;
                        }
                        if ($foundFc === null && isset($prodMap[$pid])) {
                            $doc = $prodMap[$pid]['doc'];
                            if ($doc !== '') {
                                $getProdFpByDocStmt->execute([$doc]);
                                $rawFpDoc = $getProdFpByDocStmt->fetchColumn();
                                if (is_string($rawFpDoc) && strpos($rawFpDoc, 'data:image/') !== false) {
                                    $foundFc = $rawFpDoc;
                                }
                            }
                            if ($foundFc === null && $prodMap[$pid]['norm_full'] !== '' && !empty($exactNameBuckets[$prodMap[$pid]['norm_full']])) {
                                foreach ($exactNameBuckets[$prodMap[$pid]['norm_full']] as $altPid) {
                                    $getProdFpByIdStmt->execute([$altPid]);
                                    $rawFpAlt = $getProdFpByIdStmt->fetchColumn();
                                    if (is_string($rawFpAlt) && strpos($rawFpAlt, 'data:image/') !== false) {
                                        $foundFc = $rawFpAlt;
                                        break;
                                    }
                                }
                            }
                        }
                        if ($foundFc === null && isset($donorEvalIdByParticipant[$pid])) {
                            $getEvalFcByIdStmt->execute([$donorEvalIdByParticipant[$pid]]);
                            $rawFc = $getEvalFcByIdStmt->fetchColumn();
                            if (is_string($rawFc) && strpos($rawFc, 'data:image/') !== false) {
                                $foundFc = $rawFc;
                            }
                        }
                        if ($foundFc !== null) {
                            $updFcStmt->execute([$foundFc, $eid]);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Silencioso para no interrumpir la respuesta de los endpoints
        }
    }
}
