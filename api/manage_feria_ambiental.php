<?php
/**
 * API Endpoint: Gestión de Feria Agroambiental Sumapaz
 * Acciones soportadas (POST JSON):
 * - delete_all_duplicates: Elimina en lote todos los duplicados unificando todos sus concursos diferentes.
 * - resolve_duplicate_group: Deja un único registro para la persona, uniendo todos los concursos
 *   diferentes que tenga en todos sus registros (si es el mismo concurso queda solo 1) y conservando firmas/fotos.
 * - add_concurso: Agrega un concurso a un productor.
 * - remove_concurso: Quita un concurso de un productor (y de sus copias si aún existen).
 * - set_concursos: Actualiza la lista completa de concursos de un productor (y limpia en sus copias).
 */
@set_time_limit(300);
@ini_set('max_execution_time', '300');
@ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!$isLocal && !in_array($rolId, [1, 13, 14], true) && $userId !== 14) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

function buildNotesText(string $rawNotes, array $concursoIds, array $catalogById): string {
    $obs = '';
    $posObs = stripos($rawNotes, 'OBSERVACIONES:');
    if ($posObs !== false) {
        $obs = trim(substr($rawNotes, $posObs + strlen('OBSERVACIONES:')));
    }

    $uniqIds = array_values(array_unique(array_map('intval', $concursoIds)));
    sort($uniqIds);
    $contestNames = [];
    foreach ($uniqIds as $cid) {
        if (isset($catalogById[$cid])) {
            $contestNames[] = '[' . $cid . '] ' . $catalogById[$cid];
        }
    }

    $newNotes = "CONCURSOS SELECCIONADOS:\n" . implode("\n", $contestNames);
    if ($obs !== '') {
        $newNotes .= "\n\nOBSERVACIONES:\n" . $obs;
    }
    return $newNotes;
}

/**
 * Sincroniza el bloque "CONCURSOS SELECCIONADOS:" dentro del campo notes
 * de productor_predio_AMBIENTAL preservando únicamente las observaciones reales.
 */
function syncProductorNotes(PDO $pdo, int $productorId): void {
    $stmtP = $pdo->prepare("SELECT notes FROM productor_predio_AMBIENTAL WHERE id = ?");
    $stmtP->execute([$productorId]);
    $rawNotes = (string)$stmtP->fetchColumn();

    $obs = '';
    $posObs = stripos($rawNotes, 'OBSERVACIONES:');
    if ($posObs !== false) {
        $obs = trim(substr($rawNotes, $posObs + strlen('OBSERVACIONES:')));
    }

    $stmtC = $pdo->prepare("
        SELECT DISTINCT c.id, c.nombre_concurso
        FROM inscripcion_concurso_AMBIENTAL i
        INNER JOIN estructura_concursos_AMBIENTAL c ON c.id = i.id_concurso
        WHERE i.id_productor_predio = ?
        ORDER BY c.id ASC
    ");
    $stmtC->execute([$productorId]);
    $contestNames = [];
    while ($row = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $contestNames[] = '[' . $row['id'] . '] ' . $row['nombre_concurso'];
    }

    $newNotes = "CONCURSOS SELECCIONADOS:\n" . implode("\n", $contestNames);
    if ($obs !== '') {
        $newNotes .= "\n\nOBSERVACIONES:\n" . $obs;
    }

    $stmtUpd = $pdo->prepare("UPDATE productor_predio_AMBIENTAL SET notes = ? WHERE id = ?");
    $stmtUpd->execute([$newNotes, $productorId]);
}

/**
 * Asegura que para un productor cada concurso exista máximo 1 vez en inscripcion_concurso_AMBIENTAL.
 */
function deduplicateProducerConcursos(PDO $pdo, int $productorId): void {
    $stmtAll = $pdo->prepare("SELECT id, id_concurso FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio = ? ORDER BY id ASC");
    $stmtAll->execute([$productorId]);
    $seen = [];
    foreach ($stmtAll->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cid = (int)$r['id_concurso'];
        if (isset($seen[$cid])) {
            $pdo->prepare("DELETE FROM inscripcion_concurso_AMBIENTAL WHERE id = ?")->execute([(int)$r['id']]);
        } else {
            $seen[$cid] = true;
        }
    }
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';

    if ($action === 'delete_all_duplicates' || $action === 'delete_duplicates') {
        $normStr = function ($s): string {
            $s = trim((string)$s);
            if ($s === '') return '';
            if (function_exists('iconv')) {
                $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
                if ($conv !== false) $s = $conv;
            }
            $s = strtoupper($s);
            $s = preg_replace('/[^A-Z0-9\s]/', '', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        };

        $pdo->beginTransaction();

        // 1. Catálogo de concursos en memoria (1 consulta)
        $catalogById = [];
        foreach ($pdo->query("SELECT id, nombre_concurso FROM estructura_concursos_AMBIENTAL ORDER BY id ASC") as $c) {
            $catalogById[(int)$c['id']] = $c['nombre_concurso'];
        }

        // 2. Todas las inscripciones a concursos en memoria (1 consulta)
        $inscByProd = [];
        foreach ($pdo->query("SELECT id, id_productor_predio, id_concurso FROM inscripcion_concurso_AMBIENTAL ORDER BY id ASC") as $r) {
            $pid = (int)$r['id_productor_predio'];
            $inscByProd[$pid][] = [
                'id' => (int)$r['id'],
                'id_concurso' => (int)$r['id_concurso']
            ];
        }

        // 3. Todos los productores sin imágenes base64 en memoria (1 consulta)
        $stmtAll = $pdo->query("
            SELECT id, nombre_productor, apellido_productor, numero_documento,
                   nombre_predio, extension_finca, vereda, cuenca,
                   latitud, longitud, altitud_msnm, telefono, email,
                   genero, edad, nivel_escolaridad, condicion_discapacidad,
                   nombre_inscriptor, notes, fecha_inscripcion, created_at,
                   (firma_productor IS NOT NULL AND firma_productor != '') AS has_fp,
                   (firma_inscriptor IS NOT NULL AND firma_inscriptor != '') AS has_fi,
                   (photo_paths IS NOT NULL AND photo_paths != '') AS has_photos
            FROM productor_predio_AMBIENTAL
            ORDER BY id DESC
        ");
        $rows = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        $groups = [];
        foreach ($rows as $p) {
            $doc = $normStr($p['numero_documento'] ?? '');
            if ($doc !== '') {
                $key = 'D:' . $doc;
            } else {
                $fullName = $normStr(($p['nombre_productor'] ?? '') . ' ' . ($p['apellido_productor'] ?? ''));
                $fecha = substr((string)($p['fecha_inscripcion'] ?: $p['created_at'] ?: ''), 0, 16);
                $key = 'N:' . $fullName . '|' . $fecha;
            }
            $groups[$key][] = $p;
        }

        $lightFields = [
            'nombre_predio', 'extension_finca', 'vereda', 'cuenca',
            'latitud', 'longitud', 'altitud_msnm', 'telefono', 'email',
            'genero', 'edad', 'nivel_escolaridad', 'condicion_discapacidad', 'nombre_inscriptor'
        ];

        $prodIdsToDelete = [];
        $inscIdsToDelete = [];
        $mergedConcursos = 0;

        foreach ($groups as $g) {
            if (count($g) === 1) {
                // Solo limpiar si un productor único tiene el mismo concurso repetido varias veces
                $only = $g[0];
                $onlyId = (int)$only['id'];
                $pInscs = $inscByProd[$onlyId] ?? [];
                if (count($pInscs) > 1) {
                    $seen = [];
                    foreach ($pInscs as $ir) {
                        $cid = $ir['id_concurso'];
                        if (isset($seen[$cid])) {
                            $inscIdsToDelete[] = $ir['id'];
                        } else {
                            $seen[$cid] = true;
                        }
                    }
                }
                continue;
            }

            // Priorizar el registro que tenga firmas/fotos y, en caso de empate, el ID más reciente
            usort($g, function ($a, $b) {
                $scoreA = ((int)$a['has_fp'] ? 4 : 0) + ((int)$a['has_fi'] ? 2 : 0) + ((int)$a['has_photos'] ? 1 : 0);
                $scoreB = ((int)$b['has_fp'] ? 4 : 0) + ((int)$b['has_fi'] ? 2 : 0) + ((int)$b['has_photos'] ? 1 : 0);
                if ($scoreA !== $scoreB) return $scoreB <=> $scoreA;
                return ((int)$b['id']) <=> ((int)$a['id']);
            });

            $keep = $g[0];
            $keepId = (int)$keep['id'];
            $fieldsToUpdate = [];

            // Concursos en el registro principal (eliminando repetidos internos si los hubiera)
            $seenConcursos = [];
            foreach ($inscByProd[$keepId] ?? [] as $ir) {
                $cid = $ir['id_concurso'];
                if (isset($seenConcursos[$cid])) {
                    $inscIdsToDelete[] = $ir['id'];
                } else {
                    $seenConcursos[$cid] = true;
                }
            }

            $inscIdsToMoveToKeep = [];
            $dupIdsInGroup = [];

            for ($idx = 1; $idx < count($g); $idx++) {
                $dup = $g[$idx];
                $dupId = (int)$dup['id'];
                $dupIdsInGroup[] = $dupId;
                $prodIdsToDelete[] = $dupId;

                foreach ($lightFields as $lf) {
                    $kv = $keep[$lf] ?? null;
                    $dv = $dup[$lf] ?? null;
                    if (($kv === null || $kv === '') && ($dv !== null && $dv !== '')) {
                        $keep[$lf] = $dv;
                        $fieldsToUpdate[$lf] = $dv;
                    }
                }

                if (!(int)$keep['has_fp'] && (int)$dup['has_fp']) {
                    $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.firma_productor = d.firma_productor WHERE k.id = ?")->execute([$dupId, $keepId]);
                    $keep['has_fp'] = 1;
                }
                if (!(int)$keep['has_fi'] && (int)$dup['has_fi']) {
                    $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.firma_inscriptor = d.firma_inscriptor WHERE k.id = ?")->execute([$dupId, $keepId]);
                    $keep['has_fi'] = 1;
                }
                if (!(int)$keep['has_photos'] && (int)$dup['has_photos']) {
                    $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.photo_paths = d.photo_paths WHERE k.id = ?")->execute([$dupId, $keepId]);
                    $keep['has_photos'] = 1;
                }

                // Unir todos los concursos diferentes y si ya existe el concurso dejar solo 1
                foreach ($inscByProd[$dupId] ?? [] as $ir) {
                    $cid = $ir['id_concurso'];
                    if (!isset($seenConcursos[$cid])) {
                        $seenConcursos[$cid] = true;
                        $inscIdsToMoveToKeep[] = $ir['id'];
                        $mergedConcursos++;
                    } else {
                        $inscIdsToDelete[] = $ir['id'];
                    }
                }
            }

            if (!empty($inscIdsToMoveToKeep)) {
                $inMove = implode(',', array_map('intval', $inscIdsToMoveToKeep));
                $pdo->exec("UPDATE inscripcion_concurso_AMBIENTAL SET id_productor_predio = $keepId WHERE id IN ($inMove)");
            }

            if (!empty($dupIdsInGroup)) {
                $inDups = implode(',', array_map('intval', $dupIdsInGroup));
                try {
                    $pdo->exec("UPDATE resultado_concurso_AMBIENTAL SET id_productor_predio = $keepId WHERE id_productor_predio IN ($inDups)");
                } catch (Exception $e) {
                    // tabla opcional
                }
            }

            // Actualizar notes y campos completados en una sola sentencia UPDATE para este grupo
            $newNotes = buildNotesText((string)($keep['notes'] ?? ''), array_keys($seenConcursos), $catalogById);
            if ($newNotes !== (string)($keep['notes'] ?? '')) {
                $fieldsToUpdate['notes'] = $newNotes;
            }

            if (!empty($fieldsToUpdate)) {
                $setParts = [];
                $params = [];
                foreach ($fieldsToUpdate as $col => $val) {
                    $setParts[] = "$col = ?";
                    $params[] = $val;
                }
                $params[] = $keepId;
                $pdo->prepare("UPDATE productor_predio_AMBIENTAL SET " . implode(', ', $setParts) . " WHERE id = ?")->execute($params);
            }
        }

        // Eliminaciones masivas en una sola consulta SQL
        if (!empty($inscIdsToDelete)) {
            foreach (array_chunk($inscIdsToDelete, 500) as $chunk) {
                $inDelInsc = implode(',', array_map('intval', $chunk));
                $pdo->exec("DELETE FROM inscripcion_concurso_AMBIENTAL WHERE id IN ($inDelInsc)");
            }
        }

        if (!empty($prodIdsToDelete)) {
            foreach (array_chunk($prodIdsToDelete, 500) as $chunk) {
                $inDelProd = implode(',', array_map('intval', $chunk));
                $pdo->exec("DELETE FROM productor_predio_AMBIENTAL WHERE id IN ($inDelProd)");
            }
        }

        $pdo->commit();

        $deletedProductores = count($prodIdsToDelete);
        $deletedDupInscripciones = count($inscIdsToDelete);

        echo json_encode([
            'success' => true,
            'deleted_productores' => $deletedProductores,
            'merged_concursos' => $mergedConcursos,
            'deleted_inscripciones' => $deletedDupInscripciones,
            'message' => "Se eliminaron $deletedProductores copia(s) duplicada(s), se unificaron $mergedConcursos concurso(s) diferente(s) y se depuraron $deletedDupInscripciones inscripción(es) repetida(s)."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'resolve_duplicate_group') {
        $keepId = isset($input['keep_id']) ? (int)$input['keep_id'] : 0;
        $deleteIds = isset($input['delete_ids']) && is_array($input['delete_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $input['delete_ids']), fn($v) => $v > 0 && $v !== $keepId)))
            : [];

        if ($keepId <= 0 || empty($deleteIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe indicar el registro a conservar y los duplicados a eliminar'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtKeep = $pdo->prepare("
            SELECT id, nombre_predio, extension_finca, vereda, cuenca,
                   latitud, longitud, altitud_msnm, telefono, email,
                   genero, edad, nivel_escolaridad, condicion_discapacidad, nombre_inscriptor,
                   (firma_productor IS NOT NULL AND firma_productor != '') AS has_fp,
                   (firma_inscriptor IS NOT NULL AND firma_inscriptor != '') AS has_fi,
                   (photo_paths IS NOT NULL AND photo_paths != '') AS has_photos
            FROM productor_predio_AMBIENTAL
            WHERE id = ?
        ");
        $stmtKeep->execute([$keepId]);
        $keep = $stmtKeep->fetch(PDO::FETCH_ASSOC);
        if (!$keep) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'El registro seleccionado para conservar no existe'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtExistInsc = $pdo->prepare("SELECT id_concurso FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio = ?");
        $stmtExistInsc->execute([$keepId]);
        $seenConcursos = array_flip($stmtExistInsc->fetchAll(PDO::FETCH_COLUMN));

        $lightFields = [
            'nombre_predio', 'extension_finca', 'vereda', 'cuenca',
            'latitud', 'longitud', 'altitud_msnm', 'telefono', 'email',
            'genero', 'edad', 'nivel_escolaridad', 'condicion_discapacidad', 'nombre_inscriptor'
        ];
        $fieldsToUpdate = [];
        $deletedCount = 0;

        foreach ($deleteIds as $dupId) {
            $stmtDup = $pdo->prepare("
                SELECT id, nombre_predio, extension_finca, vereda, cuenca,
                       latitud, longitud, altitud_msnm, telefono, email,
                       genero, edad, nivel_escolaridad, condicion_discapacidad, nombre_inscriptor,
                       (firma_productor IS NOT NULL AND firma_productor != '') AS has_fp,
                       (firma_inscriptor IS NOT NULL AND firma_inscriptor != '') AS has_fi,
                       (photo_paths IS NOT NULL AND photo_paths != '') AS has_photos
                FROM productor_predio_AMBIENTAL
                WHERE id = ?
            ");
            $stmtDup->execute([$dupId]);
            $dup = $stmtDup->fetch(PDO::FETCH_ASSOC);
            if (!$dup) continue;

            foreach ($lightFields as $lf) {
                $kv = $keep[$lf] ?? null;
                $dv = $dup[$lf] ?? null;
                if (($kv === null || $kv === '') && ($dv !== null && $dv !== '')) {
                    $keep[$lf] = $dv;
                    $fieldsToUpdate[$lf] = $dv;
                }
            }

            if (!(int)$keep['has_fp'] && (int)$dup['has_fp']) {
                $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.firma_productor = d.firma_productor WHERE k.id = ?")->execute([$dupId, $keepId]);
                $keep['has_fp'] = 1;
            }
            if (!(int)$keep['has_fi'] && (int)$dup['has_fi']) {
                $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.firma_inscriptor = d.firma_inscriptor WHERE k.id = ?")->execute([$dupId, $keepId]);
                $keep['has_fi'] = 1;
            }
            if (!(int)$keep['has_photos'] && (int)$dup['has_photos']) {
                $pdo->prepare("UPDATE productor_predio_AMBIENTAL k JOIN productor_predio_AMBIENTAL d ON d.id = ? SET k.photo_paths = d.photo_paths WHERE k.id = ?")->execute([$dupId, $keepId]);
                $keep['has_photos'] = 1;
            }

            $stmtDupInsc = $pdo->prepare("SELECT id, id_concurso FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio = ?");
            $stmtDupInsc->execute([$dupId]);
            foreach ($stmtDupInsc->fetchAll(PDO::FETCH_ASSOC) as $inscRow) {
                $cId = (int)$inscRow['id_concurso'];
                $inscId = (int)$inscRow['id'];
                if (!isset($seenConcursos[$cId])) {
                    $seenConcursos[$cId] = true;
                    $pdo->prepare("UPDATE inscripcion_concurso_AMBIENTAL SET id_productor_predio = ? WHERE id = ?")->execute([$keepId, $inscId]);
                } else {
                    $pdo->prepare("DELETE FROM inscripcion_concurso_AMBIENTAL WHERE id = ?")->execute([$inscId]);
                }
            }

            try {
                $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET id_productor_predio = ? WHERE id_productor_predio = ?")->execute([$keepId, $dupId]);
            } catch (Exception $e) {
                // tabla opcional
            }

            $pdo->prepare("DELETE FROM productor_predio_AMBIENTAL WHERE id = ?")->execute([$dupId]);
            $deletedCount++;
        }

        if (!empty($fieldsToUpdate)) {
            $setParts = [];
            $params = [];
            foreach ($fieldsToUpdate as $col => $val) {
                $setParts[] = "$col = ?";
                $params[] = $val;
            }
            $params[] = $keepId;
            $pdo->prepare("UPDATE productor_predio_AMBIENTAL SET " . implode(', ', $setParts) . " WHERE id = ?")->execute($params);
        }

        deduplicateProducerConcursos($pdo, $keepId);
        syncProductorNotes($pdo, $keepId);
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'keep_id' => $keepId,
            'deleted_count' => $deletedCount,
            'total_concursos' => count($seenConcursos),
            'message' => "Se conservó el registro #$keepId con todos sus concursos únicos (" . count($seenConcursos) . ") y se eliminaron $deletedCount copia(s) duplicada(s)."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add_concurso') {
        $productorId = isset($input['productor_id']) ? (int)$input['productor_id'] : 0;
        $concursoId = isset($input['concurso_id']) ? (int)$input['concurso_id'] : 0;

        if ($productorId <= 0 || $concursoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Productor y concurso son obligatorios'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT id FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio = ? AND id_concurso = ?");
        $stmtCheck->execute([$productorId, $concursoId]);
        if (!$stmtCheck->fetchColumn()) {
            $stmtIns = $pdo->prepare("INSERT INTO inscripcion_concurso_AMBIENTAL (id_productor_predio, id_concurso, fecha_inscripcion) VALUES (?, ?, ?)");
            $stmtIns->execute([$productorId, $concursoId, date('Y-m-d H:i:s')]);
        }

        deduplicateProducerConcursos($pdo, $productorId);
        syncProductorNotes($pdo, $productorId);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Concurso agregado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'remove_concurso') {
        $productorId = isset($input['productor_id']) ? (int)$input['productor_id'] : 0;
        $concursoId = isset($input['concurso_id']) ? (int)$input['concurso_id'] : 0;
        $groupIds = isset($input['group_ids']) && is_array($input['group_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $input['group_ids']), fn($v) => $v > 0)))
            : [];
        if ($productorId > 0 && !in_array($productorId, $groupIds, true)) {
            $groupIds[] = $productorId;
        }

        if (empty($groupIds) || $concursoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Productor y concurso son obligatorios'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $inPlaceholders = implode(',', array_fill(0, count($groupIds), '?'));
        $params = array_merge($groupIds, [$concursoId]);
        $stmtDel = $pdo->prepare("DELETE FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio IN ($inPlaceholders) AND id_concurso = ?");
        $stmtDel->execute($params);

        foreach ($groupIds as $gid) {
            syncProductorNotes($pdo, (int)$gid);
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Concurso eliminado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'set_concursos') {
        $productorId = isset($input['productor_id']) ? (int)$input['productor_id'] : 0;
        $concursoIds = isset($input['concurso_ids']) && is_array($input['concurso_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $input['concurso_ids']), fn($v) => $v > 0)))
            : [];
        $groupIds = isset($input['group_ids']) && is_array($input['group_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $input['group_ids']), fn($v) => $v > 0)))
            : [];
        if ($productorId > 0 && !in_array($productorId, $groupIds, true)) {
            $groupIds[] = $productorId;
        }

        if ($productorId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de productor inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $inGroup = implode(',', array_fill(0, count($groupIds), '?'));
        $stmtCur = $pdo->prepare("SELECT DISTINCT id_concurso FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio IN ($inGroup)");
        $stmtCur->execute($groupIds);
        $currentGroupConcursos = array_map('intval', $stmtCur->fetchAll(PDO::FETCH_COLUMN));

        $toRemove = array_values(array_diff($currentGroupConcursos, $concursoIds));

        if (!empty($toRemove)) {
            $inRem = implode(',', array_fill(0, count($toRemove), '?'));
            $stmtDel = $pdo->prepare("DELETE FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio IN ($inGroup) AND id_concurso IN ($inRem)");
            $stmtDel->execute(array_merge($groupIds, $toRemove));
        }

        $stmtCurMain = $pdo->prepare("SELECT id_concurso FROM inscripcion_concurso_AMBIENTAL WHERE id_productor_predio = ?");
        $stmtCurMain->execute([$productorId]);
        $currentMainIds = array_map('intval', $stmtCurMain->fetchAll(PDO::FETCH_COLUMN));

        $toAdd = array_values(array_diff($concursoIds, $currentMainIds));
        if (!empty($toAdd)) {
            $now = date('Y-m-d H:i:s');
            $stmtIns = $pdo->prepare("INSERT INTO inscripcion_concurso_AMBIENTAL (id_productor_predio, id_concurso, fecha_inscripcion) VALUES (?, ?, ?)");
            foreach ($toAdd as $cid) {
                $stmtIns->execute([$productorId, $cid, $now]);
            }
        }

        foreach ($groupIds as $gid) {
            deduplicateProducerConcursos($pdo, (int)$gid);
            syncProductorNotes($pdo, (int)$gid);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'added' => count($toAdd),
            'removed' => count($toRemove),
            'message' => 'Concursos actualizados correctamente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
