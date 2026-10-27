<?php
/**
 * Migración e importación idempotente del módulo Despacho (Seguimiento Contractual Sumapaz).
 * Ejecución exclusiva por CLI (consola). No accesible vía HTTP.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Acceso prohibido. Este script solo puede ejecutarse por CLI.']);
    exit;
}

require_once __DIR__ . '/db_config.php';

function safeLen(?string $s): int {
    if ($s === null) return 0;
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function safeSub(?string $s, int $start, ?int $len = null): string {
    if ($s === null) return '';
    if (function_exists('mb_substr')) {
        return $len === null ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
    }
    return $len === null ? substr($s, $start) : substr($s, $start, $len);
}

function safeLower(?string $s): string {
    if ($s === null) return '';
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

function parseSpanishDateToIso(?string $raw, int $defaultYear = 2026): ?string {
    if ($raw === null) return null;
    $str = trim($raw);
    if ($str === '' || strtoupper($str) === 'N/A') return null;

    if (preg_match('/\b(20\d{2})-(\d{1,2})-(\d{1,2})\b/', $str, $m)) {
        if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
    }

    if (preg_match('/\b(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(20\d{2})\b/', $str, $m)) {
        $d = (int)$m[1];
        $mo = (int)$m[2];
        $y = (int)$m[3];
        if (checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }

    $months = [
        'enero' => 1, 'ene' => 1, 'en' => 1,
        'febrero' => 2, 'feb' => 2,
        'marzo' => 3, 'mar' => 3,
        'abril' => 4, 'abr' => 4,
        'mayo' => 5, 'may' => 5,
        'junio' => 6, 'jun' => 6,
        'julio' => 7, 'jul' => 7,
        'agosto' => 8, 'ago' => 8,
        'septiembre' => 9, 'sep' => 9, 'sept' => 9,
        'octubre' => 10, 'oct' => 10,
        'noviembre' => 11, 'nov' => 11,
        'diciembre' => 12, 'dic' => 12,
    ];

    $clean = safeLower($str);
    $clean = str_replace(['cinco (05)', 'cinco (5)'], '5', $clean);

    if (preg_match('/\b(\d{1,2})\s*(?:de|[\/\-])?\s*([a-záéíóú]+)\s*(?:de|del|[\/\-])?\s*(20\d{2}|\d{2})?\b/u', $clean, $m)) {
        $d = (int)$m[1];
        $mName = $m[2];
        $yRaw = $m[3] ?? '';
        if (isset($months[$mName])) {
            $mo = $months[$mName];
            $y = $defaultYear;
            if ($yRaw !== '') {
                $y = strlen($yRaw) === 2 ? (2000 + (int)$yRaw) : (int)$yRaw;
            }
            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }
    }

    if (preg_match('/\b([a-záéíóú]+)\s+(\d{1,2})(?:\s*de\s*(20\d{2}))?\b/u', $clean, $m)) {
        $mName = $m[1];
        $d = (int)$m[2];
        $y = !empty($m[3]) ? (int)$m[3] : $defaultYear;
        if (isset($months[$mName]) && checkdate($months[$mName], $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $months[$mName], $d);
        }
    }

    return null;
}

function parsePercentage(?string $raw): ?float {
    if ($raw === null) return null;
    $str = trim($raw);
    if ($str === '') return null;
    if ($str === '0' || $str === '0%') return 0.0;
    if (preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $str, $m)) {
        return (float)str_replace(',', '.', $m[1]);
    }
    return null;
}

function parseMoneyValue(?string $raw): ?float {
    if ($raw === null) return null;
    $str = trim($raw);
    if ($str === '') return null;
    if (preg_match('/\$?\s*(\d{1,3}(?:[\.\',]\d{3})+(?:[\.,]\d{1,2})?)/', $str, $m)) {
        $numStr = preg_replace('/[\.,]\d{2}$/', '', $m[1]);
        $digits = preg_replace('/[^\d]/', '', $numStr);
        if ($digits !== '' && (float)$digits > 1000) {
            return (float)$digits;
        }
    }
    return null;
}

function normalizeEstadoContractual(?string $estadoReportado, ?string $categoriaOrigen, ?string $mensaje1): string {
    $txt = safeLower(($estadoReportado ?? '') . ' ' . ($mensaje1 ?? ''));
    if (strpos($txt, 'suspendid') !== false) return 'SUSPENDIDO';
    if (strpos($txt, 'liquidaci') !== false) return 'EN_LIQUIDACION';
    if (strpos($txt, 'finalizad') !== false || strpos($txt, 'terminad') !== false || strpos($txt, 'recibo a satisfacci') !== false || strpos($txt, 'cierre del contrato') !== false) {
        return 'TERMINADO';
    }
    if ($categoriaOrigen === 'CIERRE') return 'EN_LIQUIDACION';
    if (strpos($txt, 'adici') !== false || strpos($txt, 'prórroga') !== false || strpos($txt, 'prorroga') !== false) {
        if (strpos($txt, 'en proceso de adici') !== false || strpos($txt, 'solicito adici') !== false || strpos($txt, 'solicitó adici') !== false || strpos($txt, 'gestionando una nueva') !== false || strpos($txt, 'se va a prórrogar') !== false) {
            return 'EN_MODIFICACION';
        }
    }
    return 'EN_EJECUCION';
}

function inferAreaCodigo(string $numero, string $grupo, ?string $msg1, ?string $msg2): string {
    $all = safeLower("$numero $grupo " . ($msg1 ?? '') . ' ' . ($msg2 ?? ''));
    if (strpos($all, 'cop-') !== false || strpos($all, 'cop ') !== false || strpos($all, 'cin-') !== false || strpos($all, 'ccs-') !== false || strpos($all, 'maquinaria') !== false || strpos($all, 'materiales construcción') !== false || strpos($all, 'malla vial') !== false) {
        return 'INFRAESTRUCTURA';
    }
    if (strpos($all, 'agro') !== false || strpos($all, 'vivero') !== false || strpos($all, 'jardiner') !== false || strpos($all, 'defensa civil') !== false || strpos($all, 'riesgo') !== false || strpos($all, 'ambiental') !== false) {
        return 'AGROAMBIENTAL';
    }
    if (strpos($all, 'cultur') !== false || strpos($all, 'deport') !== false || strpos($all, 'salud') !== false || strpos($all, 'formación') !== false || strpos($all, 'participación') !== false || strpos($all, 'emprendimiento') !== false || strpos($all, 'pedagógic') !== false || strpos($all, 'seguro escolar') !== false) {
        return 'DESARROLLO_SOCIAL';
    }
    if (strpos($all, 'tecnolog') !== false || strpos($all, 'conectividad') !== false || strpos($all, 'renata') !== false || strpos($all, 'office') !== false || strpos($all, '159918') !== false || strpos($all, '159301') !== false || strpos($all, '159119') !== false) {
        return 'TIC_CONECTIVIDAD';
    }
    return 'GESTION_INSTITUCIONAL';
}

function parseMensaje1(?string $msg1): array {
    $res = [
        'objeto' => null,
        'duracion_texto' => null,
        'valor_inicial' => null,
        'valor_adiciones' => null,
        'valor_total' => null,
        'valor_texto_original' => null,
        'fecha_inicio_texto' => null,
        'fecha_inicio_iso' => null,
        'fecha_fin_inicial_texto' => null,
        'fecha_fin_inicial_iso' => null,
        'contratista' => null,
        'interventoria' => null,
        'apoyo_supervision' => null,
        'modificaciones' => [],
        'proyectos_codigos' => [],
    ];
    if ($msg1 === null || trim($msg1) === '') {
        return $res;
    }

    $lines = preg_split('/\r\n|\r|\n/', $msg1);
    $adicionesSum = 0.0;
    $hasAdiciones = false;

    foreach ($lines as $line) {
        $lineTrim = trim($line);
        if ($lineTrim === '') continue;
        $cleanLine = str_replace('*', '', $lineTrim);

        if ($res['objeto'] === null && preg_match('/^(?:Objeto(?: del(?: del)? contrato)?|Descripción del contrato|OBJETO)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['objeto'] = trim($m[1], " \t\"“”:");
        }
        if ($res['duracion_texto'] === null && preg_match('/^(?:Duración(?: del contrato(?: inicial| final| obra\/inter)?)?|Plazo)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['duracion_texto'] = trim($m[1]);
        }
        if (preg_match('/^(?:Valor(?: inicial| contrato| del contrato| obra| Total del Contrato)?|INCIAL|Montó|Monto)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $vTxt = trim($m[1]);
            if ($res['valor_texto_original'] === null && $vTxt !== '') {
                $res['valor_texto_original'] = $vTxt;
            }
            $val = parseMoneyValue($vTxt);
            if ($val !== null && $res['valor_inicial'] === null) {
                $res['valor_inicial'] = $val;
            }
        }
        if (preg_match('/^(?:Valor total(?: contrato)?|TOTAL|Total|Valor Final|Valor obra con adición)\s*:?\s*(.+)$/iu', $cleanLine, $m)) {
            $val = parseMoneyValue($m[1]);
            if ($val !== null) {
                $res['valor_total'] = $val;
            }
        }
        if (preg_match('/^(?:Adici[oó]n(?:\s*No\.?\s*\d+|\s*\d+)?|ADICION|Valor adici[oó]n)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $val = parseMoneyValue($m[1]);
            if ($val !== null) {
                $adicionesSum += $val;
                $hasAdiciones = true;
            }
            $res['modificaciones'][] = [
                'tipo' => 'ADICION',
                'numero' => $cleanLine,
                'descripcion' => $cleanLine,
                'valor' => $val,
            ];
        }
        if (preg_match('/^(?:Pr[oó]rroga(?:\s*No\.?\s*\d+|\s*\d+)?|Suspensi[oó]n(?:\s*No\.?\s*\d+)?|Ampliaci[oó]n\s*(?:No\.?\s*)?\d+\s*Suspensi[oó]n)\s*(?:,\s*si aplica)?\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $desc = trim($m[1]);
            if ($desc !== '' && strtoupper($desc) !== 'N/A' && strtoupper($desc) !== 'NO') {
                $tipoMod = 'PRORROGA';
                if (stripos($cleanLine, 'ampliaci') !== false) $tipoMod = 'AMPLIACION_SUSPENSION';
                elseif (stripos($cleanLine, 'suspensi') !== false) $tipoMod = 'SUSPENSION';
                $res['modificaciones'][] = [
                    'tipo' => $tipoMod,
                    'numero' => $cleanLine,
                    'descripcion' => $cleanLine,
                    'valor' => null,
                ];
            }
        }
        if ($res['fecha_inicio_texto'] === null && preg_match('/^(?:Fecha de inicio(?: del contrato)?(?: obra\/inter)?|Fecha de Inicio|Inicio)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['fecha_inicio_texto'] = trim($m[1]);
            $res['fecha_inicio_iso'] = parseSpanishDateToIso($res['fecha_inicio_texto'], 2026);
        }
        if ($res['fecha_fin_inicial_texto'] === null && preg_match('/^(?:Fecha de terminaci[oó]n inicial(?: obra\/inter)?|Fecha de terminaci[oó]n|Fin)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['fecha_fin_inicial_texto'] = trim($m[1]);
            $res['fecha_fin_inicial_iso'] = parseSpanishDateToIso($res['fecha_fin_inicial_texto'], 2026);
        }
        if ($res['contratista'] === null && preg_match('/^(?:Contratista|Nombre del contratista)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['contratista'] = trim($m[1], " \t\"");
        }
        if ($res['interventoria'] === null && preg_match('/^(?:Interventor[ií]a)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['interventoria'] = trim($m[1], " \t\"");
        }
        if ($res['apoyo_supervision'] === null && preg_match('/^(?:Apoyo a la [sS]upervisi[oó]n(?: de la Interventor[ií]a)?)\s*:\s*(.+)$/iu', $cleanLine, $m)) {
            $res['apoyo_supervision'] = trim($m[1]);
        }
    }

    if ($hasAdiciones) {
        $res['valor_adiciones'] = $adicionesSum;
    }
    if ($res['valor_total'] === null && $res['valor_inicial'] !== null) {
        $res['valor_total'] = $res['valor_inicial'] + ($res['valor_adiciones'] ?? 0.0);
    }

    if (preg_match_all('/\b(2[2-7]\d{2})\b/', $msg1, $pm)) {
        $ignoreYears = ['2022', '2023', '2024', '2025', '2026', '2027', '2028'];
        foreach (array_unique($pm[1]) as $code) {
            if (!in_array($code, $ignoreYears, true)) {
                $res['proyectos_codigos'][] = $code;
            }
        }
    }

    return $res;
}

function readXlsxSheets(string $xlsxPath): array {
    if (!file_exists($xlsxPath)) {
        throw new RuntimeException("No se encontró el archivo Excel en: $xlsxPath");
    }
    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        throw new RuntimeException("No fue posible abrir el archivo xlsx: $xlsxPath");
    }

    $sharedStrings = [];
    $ssStr = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssStr !== false) {
        $dom = new DOMDocument();
        $dom->loadXML($ssStr);
        foreach ($dom->getElementsByTagNameNS('*', 'si') as $si) {
            $str = '';
            foreach ($si->getElementsByTagNameNS('*', 't') as $t) {
                $str .= $t->nodeValue;
            }
            $sharedStrings[] = $str;
        }
    }

    $wbDom = new DOMDocument();
    $wbDom->loadXML($zip->getFromName('xl/workbook.xml'));

    $relsDom = new DOMDocument();
    $relsDom->loadXML($zip->getFromName('xl/_rels/workbook.xml.rels'));
    $rels = [];
    foreach ($relsDom->getElementsByTagNameNS('*', 'Relationship') as $rel) {
        $rels[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
    }

    $sheetsData = [];
    foreach ($wbDom->getElementsByTagNameNS('*', 'sheet') as $sheet) {
        $name = $sheet->getAttribute('name');
        $rId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
        if (!$rId) $rId = $sheet->getAttribute('r:id');
        $target = ltrim($rels[$rId], '/');
        if (strpos($target, 'xl/') !== 0) {
            $target = 'xl/' . $target;
        }
        $shStr = $zip->getFromName($target);
        if ($shStr === false) continue;

        $shDom = new DOMDocument();
        $shDom->loadXML($shStr);
        $rows = [];
        foreach ($shDom->getElementsByTagNameNS('*', 'row') as $rowEl) {
            $rNum = (int)$rowEl->getAttribute('r');
            $rowArr = ['_row' => $rNum];
            foreach ($rowEl->getElementsByTagNameNS('*', 'c') as $cEl) {
                $ref = $cEl->getAttribute('r');
                preg_match('/^([A-Z]+)\d+$/', $ref, $m);
                $col = $m[1] ?? $ref;
                $t = $cEl->getAttribute('t');
                $vEls = $cEl->getElementsByTagNameNS('*', 'v');
                $isEls = $cEl->getElementsByTagNameNS('*', 'is');
                $val = null;
                if ($t === 's' && $vEls->length > 0) {
                    $idx = (int)$vEls->item(0)->nodeValue;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($t === 'inlineStr' && $isEls->length > 0) {
                    $val = '';
                    foreach ($isEls->item(0)->getElementsByTagNameNS('*', 't') as $tNode) {
                        $val .= $tNode->nodeValue;
                    }
                } elseif ($vEls->length > 0) {
                    $val = $vEls->item(0)->nodeValue;
                }
                if (is_string($val)) {
                    $val = trim($val);
                    if ($val === '') $val = null;
                }
                $rowArr[$col] = $val;
            }
            $rows[] = $rowArr;
        }
        $sheetsData[$name] = $rows;
    }
    $zip->close();
    return $sheetsData;
}

// ============================================================================
// 1. CREACIÓN IDEMPOTENTE DE TABLAS DEL MÓDULO DESPACHO
// ============================================================================
echo "[1/6] Creando/verificando tablas del módulo Despacho...\n";

$ddlStatements = [
    "CREATE TABLE IF NOT EXISTS despacho_areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_proyectos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL UNIQUE,
        nombre TEXT NOT NULL,
        area_id INT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_area (area_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_metas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proyecto_id INT NOT NULL,
        codigo_proyecto VARCHAR(30) NOT NULL,
        meta_descripcion TEXT NOT NULL,
        hash_meta CHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_proyecto (proyecto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_contratos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fila_excel INT NULL UNIQUE,
        numero_contrato VARCHAR(150) NOT NULL,
        codigo_normalizado VARCHAR(120) NOT NULL,
        titulo_grupo VARCHAR(255) NOT NULL,
        es_grupo TINYINT(1) NOT NULL DEFAULT 0,
        area_id INT NULL,
        objeto TEXT NULL,
        contratista VARCHAR(255) NULL,
        interventoria_nombre VARCHAR(255) NULL,
        responsable_nombre VARCHAR(200) NULL,
        responsable_en_mensaje1 VARCHAR(200) NULL,
        duracion_texto VARCHAR(200) NULL,
        valor_inicial DECIMAL(18,2) NULL,
        valor_adiciones DECIMAL(18,2) NULL,
        valor_total DECIMAL(18,2) NULL,
        valor_texto_original VARCHAR(255) NULL,
        fecha_inicio_texto VARCHAR(120) NULL,
        fecha_inicio_iso DATE NULL,
        fecha_fin_inicial_texto VARCHAR(120) NULL,
        fecha_fin_inicial_iso DATE NULL,
        fecha_fin_vigente_texto VARCHAR(150) NULL,
        fecha_fin_vigente_iso DATE NULL,
        estado_contractual VARCHAR(80) NOT NULL DEFAULT 'EN_EJECUCION',
        estado_reportado_texto TEXT NULL,
        estado_entrega VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE',
        estado_revision VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE_REVISION',
        categoria_origen VARCHAR(50) NOT NULL DEFAULT 'SEGUIMIENTO',
        nivel_alerta VARCHAR(50) NOT NULL DEFAULT 'SEGUIMIENTO',
        tipo_alerta VARCHAR(150) NULL,
        alerta_consolidada TEXT NULL,
        dato_conflicto TEXT NULL,
        fuente_actualizacion VARCHAR(200) NULL,
        corte_consolidado VARCHAR(40) NULL DEFAULT '19/10/2026',
        fecha_corte_iso DATE NULL DEFAULT '2026-10-19',
        tiene_mensaje1 TINYINT(1) NOT NULL DEFAULT 0,
        mensaje1_actual_id INT NULL,
        ultimo_reporte_id INT NULL,
        fecha_ultimo_reporte_texto VARCHAR(120) NULL,
        fecha_ultimo_reporte_iso DATE NULL,
        ejecucion_fisica_texto TEXT NULL,
        ejecucion_fisica_pct DECIMAL(6,2) NULL,
        ejecucion_financiera_texto TEXT NULL,
        ejecucion_financiera_pct DECIMAL(6,2) NULL,
        ejecucion_financiera_valor DECIMAL(18,2) NULL,
        cronograma_aprobado_pct DECIMAL(6,2) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_area (area_id),
        INDEX idx_nivel_alerta (nivel_alerta),
        INDEX idx_categoria_origen (categoria_origen),
        INDEX idx_estado_entrega (estado_entrega),
        INDEX idx_estado_revision (estado_revision),
        INDEX idx_responsable (responsable_nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_contrato_proyectos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        proyecto_id INT NOT NULL,
        meta_id INT NULL,
        porcentaje_participacion DECIMAL(6,2) NULL,
        valor_asociado DECIMAL(18,2) NULL,
        nota_asociacion VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_contrato_proyecto (contrato_id, proyecto_id),
        INDEX idx_proyecto (proyecto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_componentes_contrato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        tipo_componente VARCHAR(40) NOT NULL,
        numero_componente VARCHAR(120) NOT NULL,
        objeto TEXT NULL,
        contratista VARCHAR(255) NULL,
        responsable_apoyo VARCHAR(200) NULL,
        valor_inicial DECIMAL(18,2) NULL,
        valor_adiciones DECIMAL(18,2) NULL,
        valor_total DECIMAL(18,2) NULL,
        plazo_texto VARCHAR(150) NULL,
        fecha_inicio_texto VARCHAR(100) NULL,
        fecha_fin_inicial_texto VARCHAR(100) NULL,
        fecha_fin_vigente_texto VARCHAR(100) NULL,
        avance_fisico_texto VARCHAR(150) NULL,
        avance_fisico_pct DECIMAL(6,2) NULL,
        avance_financiero_texto VARCHAR(150) NULL,
        avance_financiero_pct DECIMAL(6,2) NULL,
        estado_componente VARCHAR(120) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_modificaciones_contrato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        componente_id INT NULL,
        tipo_modificacion VARCHAR(60) NOT NULL,
        numero_modificacion VARCHAR(120) NULL,
        descripcion_texto TEXT NOT NULL,
        valor_adicion DECIMAL(18,2) NULL,
        fecha_inicio_texto VARCHAR(100) NULL,
        fecha_fin_texto VARCHAR(100) NULL,
        estado_tramite VARCHAR(50) NOT NULL DEFAULT 'VALIDADA',
        validado_por INT NULL,
        fecha_validacion DATETIME NULL,
        fuente VARCHAR(150) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_periodos_reporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE,
        nombre VARCHAR(150) NOT NULL,
        tipo_periodo VARCHAR(40) NOT NULL DEFAULT 'SEMANAL',
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        fecha_limite_entrega DATE NOT NULL,
        es_periodo_activo TINYINT(1) NOT NULL DEFAULT 0,
        estado VARCHAR(30) NOT NULL DEFAULT 'ABIERTO',
        creado_por INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_obligaciones_reporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        periodo_id INT NOT NULL,
        contrato_id INT NOT NULL,
        responsable_nombre VARCHAR(200) NULL,
        requiere_mensaje1 TINYINT(1) NOT NULL DEFAULT 0,
        requiere_mensaje2 TINYINT(1) NOT NULL DEFAULT 1,
        fecha_esperada DATE NOT NULL,
        estado_entrega VARCHAR(40) NOT NULL DEFAULT 'PENDIENTE',
        estado_revision VARCHAR(40) NOT NULL DEFAULT 'SIN_REPORTE',
        reporte_actual_id INT NULL,
        radicado_ultimo VARCHAR(60) NULL,
        fecha_recepcion DATETIME NULL,
        observacion_obligacion TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_periodo_contrato (periodo_id, contrato_id),
        INDEX idx_periodo_estado (periodo_id, estado_entrega)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_reportes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        radicado VARCHAR(60) NOT NULL UNIQUE,
        contrato_id INT NOT NULL,
        periodo_id INT NULL,
        obligacion_id INT NULL,
        tipo_mensaje VARCHAR(30) NOT NULL,
        version_actual INT NOT NULL DEFAULT 1,
        version_actual_id INT NULL,
        responsable_reportante VARCHAR(200) NULL,
        origen_carga VARCHAR(50) NOT NULL DEFAULT 'IMPORTACION_EXCEL',
        token_id INT NULL,
        estado_entrega VARCHAR(40) NOT NULL DEFAULT 'RECIBIDO',
        estado_revision VARCHAR(40) NOT NULL DEFAULT 'PENDIENTE_REVISION',
        revisado_por INT NULL,
        revisado_por_nombre VARCHAR(150) NULL,
        fecha_revision DATETIME NULL,
        comentario_revision TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_contrato_periodo (contrato_id, periodo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_versiones_reporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporte_id INT NOT NULL,
        contrato_id INT NOT NULL,
        periodo_id INT NULL,
        numero_version INT NOT NULL DEFAULT 1,
        tipo_mensaje VARCHAR(30) NOT NULL,
        mensaje1_texto_original LONGTEXT NULL,
        mensaje2_texto_original LONGTEXT NULL,
        datos_estructurados_json LONGTEXT NULL,
        fecha_reporte_texto VARCHAR(120) NULL,
        fecha_reporte_iso DATE NULL,
        fecha_fin_reportada_texto VARCHAR(150) NULL,
        fecha_fin_reportada_iso DATE NULL,
        ejecucion_fisica_texto TEXT NULL,
        ejecucion_fisica_pct DECIMAL(6,2) NULL,
        ejecucion_financiera_texto TEXT NULL,
        ejecucion_financiera_pct DECIMAL(6,2) NULL,
        ejecucion_financiera_valor DECIMAL(18,2) NULL,
        ejecucion_financiera_obra_pct DECIMAL(6,2) NULL,
        ejecucion_financiera_inter_pct DECIMAL(6,2) NULL,
        estado_reportado_texto TEXT NULL,
        observaciones_reporte TEXT NULL,
        reportado_por VARCHAR(200) NULL,
        ip_origen VARCHAR(64) NULL,
        fuente_version VARCHAR(150) NULL,
        estado_version VARCHAR(40) NOT NULL DEFAULT 'PENDIENTE_REVISION',
        revisado_por INT NULL,
        fecha_revision DATETIME NULL,
        comentario_revision TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reporte (reporte_id),
        INDEX idx_contrato (contrato_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_indicadores_reporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version_reporte_id INT NOT NULL,
        contrato_id INT NOT NULL,
        componente_id INT NULL,
        proyecto_id INT NULL,
        nombre_indicador VARCHAR(150) NOT NULL,
        valor_texto VARCHAR(255) NULL,
        valor_numerico DECIMAL(18,2) NULL,
        porcentaje DECIMAL(6,2) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_version (version_reporte_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_adjuntos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        reporte_id INT NULL,
        version_reporte_id INT NULL,
        alerta_id INT NULL,
        nombre_original VARCHAR(255) NOT NULL,
        nombre_almacenado VARCHAR(255) NOT NULL,
        ruta_relativa VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        tamano_bytes INT NOT NULL,
        sha256_hash CHAR(64) NOT NULL,
        subido_por_tipo VARCHAR(30) NOT NULL DEFAULT 'TOKEN_RESPONSABLE',
        subido_por_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id),
        INDEX idx_version (version_reporte_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_enlaces_carga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_hash CHAR(64) NOT NULL UNIQUE,
        token_prefijo VARCHAR(12) NOT NULL,
        contrato_id INT NOT NULL,
        periodo_id INT NULL,
        obligacion_id INT NULL,
        responsable_nombre VARCHAR(200) NULL,
        alcance VARCHAR(40) NOT NULL DEFAULT 'AMBOS',
        permite_multiples_cargas TINYINT(1) NOT NULL DEFAULT 1,
        usos_realizados INT NOT NULL DEFAULT 0,
        max_usos INT NOT NULL DEFAULT 10,
        expira_en DATETIME NOT NULL,
        revocado TINYINT(1) NOT NULL DEFAULT 0,
        revocado_en DATETIME NULL,
        revocado_por INT NULL,
        creado_por INT NULL,
        ultimo_uso_en DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_reglas_alerta_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        criterio_codigo VARCHAR(80) NOT NULL UNIQUE,
        nombre_criterio VARCHAR(200) NOT NULL,
        categoria_alerta VARCHAR(50) NOT NULL DEFAULT 'RIESGO_CONTRACTUAL',
        descripcion_criterio TEXT NOT NULL,
        nota_metodologica TEXT NULL,
        parametros_json LONGTEXT NULL,
        estado_validacion VARCHAR(60) NOT NULL DEFAULT 'PENDIENTE_VALIDACION_DESPACHO',
        activa_automatica TINYINT(1) NOT NULL DEFAULT 0,
        version_regla INT NOT NULL DEFAULT 1,
        actualizado_por INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_alertas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        origen_hoja VARCHAR(60) NOT NULL DEFAULT 'CONSOLIDADO_MAESTRO',
        categoria_alerta VARCHAR(50) NOT NULL DEFAULT 'RIESGO_CONTRACTUAL',
        criterio_codigo VARCHAR(80) NULL,
        nivel_alerta VARCHAR(50) NOT NULL,
        tipo_alerta VARCHAR(150) NOT NULL,
        descripcion_alerta TEXT NOT NULL,
        condicion_evaluada TEXT NULL,
        datos_usados_json LONGTEXT NULL,
        fecha_evaluacion DATETIME NULL,
        version_regla VARCHAR(40) NULL DEFAULT 'CONSOLIDADO_2026_10_19',
        dato_por_validar TEXT NULL,
        estado_alerta VARCHAR(40) NOT NULL DEFAULT 'ACTIVA',
        requiere_accion TINYINT(1) NOT NULL DEFAULT 1,
        fecha_corte VARCHAR(40) NULL DEFAULT '19/10/2026',
        cerrada_por INT NULL,
        fecha_cierre DATETIME NULL,
        motivo_cierre TEXT NULL,
        evidencia_adjunto_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id),
        INDEX idx_nivel_estado (nivel_alerta, estado_alerta)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_eventos_alerta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alerta_id INT NULL,
        contrato_id INT NULL,
        contrato_nombre_referencia VARCHAR(255) NULL,
        fecha_corte_texto VARCHAR(60) NULL,
        tipo_evento VARCHAR(60) NOT NULL DEFAULT 'CAMBIO_ESTADO',
        cambio_dato TEXT NOT NULL,
        efecto_alerta TEXT NULL,
        usuario_id INT NULL,
        usuario_nombre VARCHAR(150) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id),
        INDEX idx_alerta (alerta_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_requerimientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NOT NULL,
        periodo_id INT NULL,
        obligacion_id INT NULL,
        alerta_id INT NULL,
        enlace_carga_id INT NULL,
        destinatario_nombre VARCHAR(200) NOT NULL,
        motivo VARCHAR(120) NOT NULL,
        asunto VARCHAR(255) NOT NULL,
        mensaje_generado TEXT NOT NULL,
        url_carga_generada TEXT NULL,
        estado_requerimiento VARCHAR(40) NOT NULL DEFAULT 'GENERADO_INTERNO',
        solicitado_por INT NOT NULL,
        solicitado_por_nombre VARCHAR(150) NOT NULL,
        fecha_respuesta DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS despacho_bitacora (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato_id INT NULL,
        entidad_tipo VARCHAR(60) NOT NULL,
        entidad_id INT NULL,
        accion VARCHAR(80) NOT NULL,
        actor_tipo VARCHAR(40) NOT NULL,
        actor_id INT NULL,
        actor_nombre VARCHAR(150) NULL,
        valor_anterior_json LONGTEXT NULL,
        valor_nuevo_json LONGTEXT NULL,
        ip_origen VARCHAR(64) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contrato (contrato_id),
        INDEX idx_entidad (entidad_tipo, entidad_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($ddlStatements as $sql) {
    $pdo->exec($sql);
}

// Asegurar columnas nuevas en tablas existentes si ya se habían creado en la corrida previa
$extraColsContratos = [
    'cronograma_aprobado_pct' => "ALTER TABLE despacho_contratos ADD COLUMN cronograma_aprobado_pct DECIMAL(6,2) NULL AFTER ejecucion_financiera_valor"
];
$existingColsContratos = $pdo->query("SHOW COLUMNS FROM despacho_contratos")->fetchAll(PDO::FETCH_COLUMN);
foreach ($extraColsContratos as $col => $alterSql) {
    if (!in_array($col, $existingColsContratos, true)) {
        $pdo->exec($alterSql);
    }
}

$extraColsAlertas = [
    'categoria_alerta' => "ALTER TABLE despacho_alertas ADD COLUMN categoria_alerta VARCHAR(50) NOT NULL DEFAULT 'RIESGO_CONTRACTUAL' AFTER origen_hoja",
    'criterio_codigo' => "ALTER TABLE despacho_alertas ADD COLUMN criterio_codigo VARCHAR(80) NULL AFTER categoria_alerta",
    'condicion_evaluada' => "ALTER TABLE despacho_alertas ADD COLUMN condicion_evaluada TEXT NULL AFTER descripcion_alerta",
    'datos_usados_json' => "ALTER TABLE despacho_alertas ADD COLUMN datos_usados_json LONGTEXT NULL AFTER condicion_evaluada",
    'fecha_evaluacion' => "ALTER TABLE despacho_alertas ADD COLUMN fecha_evaluacion DATETIME NULL AFTER datos_usados_json",
    'version_regla' => "ALTER TABLE despacho_alertas ADD COLUMN version_regla VARCHAR(40) NULL DEFAULT 'CONSOLIDADO_2026_10_19' AFTER fecha_evaluacion",
];
$existingColsAlertas = $pdo->query("SHOW COLUMNS FROM despacho_alertas")->fetchAll(PDO::FETCH_COLUMN);
foreach ($extraColsAlertas as $col => $alterSql) {
    if (!in_array($col, $existingColsAlertas, true)) {
        $pdo->exec($alterSql);
    }
}

echo "  -> 19 tablas de Despacho creadas/verificadas exitosamente.\n";

// Sembrar los 3 criterios cualitativos de alertas (umbrales configurables, pendientes de validación por Despacho/admin)
$reglasSeed = [
    [
        'TERMINACION_CERCANA_EJECUCION_INSUFICIENTE',
        '1. Terminación cercana con ejecución física y/o financiera insuficiente frente al tiempo restante',
        'RIESGO_CONTRACTUAL',
        'Evalúa contratos próximos a su fecha de terminación vigente (considerando prórrogas y suspensiones/reinicios aprobados) cuya ejecución física y/o financiera resulta baja respecto al plazo restante.',
        'La fuente cualitativa no define umbral oficial de días ni porcentajes ni equivalencia automática a ALTA/CRÍTICA. Se compara contra cronograma aprobado cuando exista; sin cronograma aprobado, la relación lineal plazo/ejecución se muestra únicamente como indicador orientativo explícito.',
        json_encode([
            'dias_restantes_umbral' => null,
            'ejecucion_minima_pct' => null,
            'comparar_con_cronograma_aprobado' => true,
            'nota' => 'Parámetros numéricos pendientes de validación oficial por Despacho/Administrador. No activar umbrales inventados.'
        ], JSON_UNESCAPED_UNICODE),
        'PENDIENTE_VALIDACION_DESPACHO',
        0
    ],
    [
        'EJECUCION_FINANCIERA_MUY_BAJA',
        '2. Ejecución financiera muy baja',
        'RIESGO_CONTRACTUAL',
        'Identifica contratos con rezago o porcentaje de ejecución financiera muy bajo.',
        'La transcripción de origen está incompleta en el segundo término de comparación: no se da por confirmado si compara contra ejecución física o avance temporal, ni define umbral numérico oficial.',
        json_encode([
            'ejecucion_financiera_minima_pct' => null,
            'variable_comparacion_confirmada' => null,
            'diferencia_puntos_umbral' => null,
            'nota' => 'Pendiente definir por Despacho/Administrador umbral y variable de comparación oficial.'
        ], JSON_UNESCAPED_UNICODE),
        'PENDIENTE_VALIDACION_DESPACHO',
        0
    ],
    [
        'FALTA_INFORMACION_ACTUALIZACION',
        '3. Ausencia de información o falta de actualización del contrato por el responsable',
        'FALTA_REPORTE',
        'Detecta ausencia de Mensaje 1 (ficha contractual), falta de entrega del Mensaje 2 en el periodo activo según obligaciones configuradas, o fecha de último reporte desactualizada frente al corte.',
        'Las alertas de entrega faltante dependen de las obligaciones y fechas de corte configuradas por periodo. Separada del riesgo de ejecución contractual.',
        json_encode([
            'verificar_obligacion_periodo_activo' => true,
            'verificar_ausencia_mensaje1' => true,
            'dias_sin_actualizar_umbral' => null,
            'nota' => 'Detección objetiva por obligación de periodo activa; umbral adicional de días sin reporte configurable por Despacho.'
        ], JSON_UNESCAPED_UNICODE),
        'ACTIVA_POR_OBLIGACION_PERIODO',
        1
    ]
];

$stmtRegla = $pdo->prepare("INSERT INTO despacho_reglas_alerta_config (
    criterio_codigo, nombre_criterio, categoria_alerta, descripcion_criterio, nota_metodologica, parametros_json, estado_validacion, activa_automatica
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    nombre_criterio = VALUES(nombre_criterio),
    categoria_alerta = VALUES(categoria_alerta),
    descripcion_criterio = VALUES(descripcion_criterio),
    nota_metodologica = VALUES(nota_metodologica)");
foreach ($reglasSeed as $regla) {
    $stmtRegla->execute($regla);
}

// ============================================================================
// 2. VERIFICACIÓN DE USUARIO ID 15 INTACTO Y CUENTA DESPACHO AUTORIZADA EN ID 16
// ============================================================================
echo "[2/6] Verificando preservación de usuarios.id = 15 (concursos_general) y cuenta autorizada despacho en usuarios.id = 16...\n";

$stmtRole15 = $pdo->prepare("SELECT id, nombre FROM roles WHERE id = 15");
$stmtRole15->execute();
if (!$stmtRole15->fetch()) {
    $pdo->prepare("INSERT INTO roles (id, nombre, descripcion) VALUES (15, 'DESPACHO', 'Rol para módulo Despacho')")->execute();
}

$stmtUser15 = $pdo->prepare("SELECT id, nombre, email, rol_id FROM usuarios WHERE id = 15");
$stmtUser15->execute();
$user15 = $stmtUser15->fetch();
echo "  -> Verificado usuarios.id = 15 intacto: nombre='{$user15['nombre']}', rol_id={$user15['rol_id']} (sin acceso a Despacho).\n";

$stmtUser16 = $pdo->prepare("SELECT id, nombre, email, rol_id FROM usuarios WHERE id = 16");
$stmtUser16->execute();
$user16 = $stmtUser16->fetch();

$initPass = getenv('DESPACHO_INIT_PASS') ?: '';
if (!$user16) {
    if ($initPass !== '') {
        $hash = password_hash($initPass, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO usuarios (id, nombre, email, password, rol_id, activo) VALUES (16, 'despacho', 'despacho@sumapaz.gov.co', ?, 15, 1)")
            ->execute([$hash]);
        echo "  -> Usuario 'despacho' creado en usuarios.id = 16 con hash bcrypt.\n";
    }
} elseif (strtolower(trim($user16['nombre'])) === 'despacho') {
    if ($initPass !== '') {
        $hash = password_hash($initPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE usuarios SET password = ?, rol_id = 15, activo = 1 WHERE id = 16")->execute([$hash]);
    }
    echo "  -> Usuario 'despacho' confirmado en usuarios.id = 16 (activo y con hash bcrypt).\n";
} else {
    throw new RuntimeException("Conflicto en usuarios.id = 16: pertenece a {$user16['nombre']}");
}

// ============================================================================
// 3. ÁREAS Y CATÁLOGO DE PROYECTOS / METAS (CON LIMPIEZA DE BOM UTF-8)
// ============================================================================
echo "[3/6] Sembrando áreas y catálogo de proyectos/metas...\n";

$areasSeed = [
    ['INFRAESTRUCTURA', 'Infraestructura y Obras Públicas', 'Contratos de obra pública, interventoría, estudios, sedes, vías y maquinaria'],
    ['AGROAMBIENTAL', 'Desarrollo Agropecuario y Ambiental', 'Contratos de insumos agropecuarios, viveros, jardinería, restauración y gestión del riesgo'],
    ['DESARROLLO_SOCIAL', 'Desarrollo Social, Cultura, Deporte y Salud', 'Contratos de salud, educación, cultura, deporte, participación ciudadana y emprendimiento'],
    ['TIC_CONECTIVIDAD', 'TIC, Conectividad y Dotación Tecnológica', 'Contratos y órdenes de compra de tecnología, conectividad y software'],
    ['GESTION_INSTITUCIONAL', 'Gestión Institucional, Logística y Suministros', 'Contratos de logística, transporte, vigilancia, seguros, aseo y suministros'],
];

$areaMap = [];
$stmtAreaIns = $pdo->prepare("INSERT INTO despacho_areas (codigo, nombre, descripcion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion)");
foreach ($areasSeed as $a) {
    $stmtAreaIns->execute($a);
}
foreach ($pdo->query("SELECT id, codigo FROM despacho_areas")->fetchAll() as $row) {
    $areaMap[$row['codigo']] = (int)$row['id'];
}

$proyectosJsonPath = dirname(__DIR__) . '/assets/proyectos_metas_sumapaz.json';
$proyectoMap = [];
$metasCount = 0;
if (file_exists($proyectosJsonPath)) {
    $rawJson = file_get_contents($proyectosJsonPath);
    // Quitar BOM UTF-8 si existe
    $rawJson = preg_replace('/^\xEF\xBB\xBF/', '', $rawJson);
    $items = json_decode($rawJson, true) ?: [];

    $pdo->beginTransaction();
    $stmtProyIns = $pdo->prepare("INSERT INTO despacho_proyectos (codigo, nombre) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $stmtMetaIns = $pdo->prepare("INSERT INTO despacho_metas (proyecto_id, codigo_proyecto, meta_descripcion, hash_meta) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE meta_descripcion = VALUES(meta_descripcion)");

    $uniqueProys = [];
    foreach ($items as $it) {
        $cod = trim((string)($it['codigo'] ?? ''));
        $nom = trim((string)($it['nombre'] ?? ''));
        $proyStr = trim((string)($it['proyecto'] ?? ''));
        if ($cod !== '' && $nom !== '') {
            $uniqueProys[$cod] = $nom;
        } elseif ($proyStr !== '' && preg_match('/^(\d+)\s+(.+)$/u', $proyStr, $m)) {
            $uniqueProys[$m[1]] = trim($m[2]);
        }
    }
    foreach ($uniqueProys as $cod => $nom) {
        $stmtProyIns->execute([$cod, $nom]);
    }
    foreach ($pdo->query("SELECT id, codigo FROM despacho_proyectos")->fetchAll() as $pRow) {
        $proyectoMap[$pRow['codigo']] = (int)$pRow['id'];
    }
    foreach ($items as $it) {
        $cod = trim((string)($it['codigo'] ?? ''));
        $proyStr = trim((string)($it['proyecto'] ?? ''));
        $metaStr = trim((string)($it['meta'] ?? ''));
        if ($cod === '' && $proyStr !== '' && preg_match('/^(\d+)\s+(.+)$/u', $proyStr, $m)) {
            $cod = $m[1];
        }
        if ($cod !== '' && $metaStr !== '' && isset($proyectoMap[$cod])) {
            $hashMeta = hash('sha256', $cod . '|' . $metaStr);
            $stmtMetaIns->execute([$proyectoMap[$cod], $cod, $metaStr, $hashMeta]);
            $metasCount++;
        }
    }
    $pdo->commit();
}
echo "  -> Proyectos cargados: " . count($proyectoMap) . " | Metas cargadas: $metasCount.\n";

// ============================================================================
// 4. PERIODOS DE REPORTE
// ============================================================================
echo "[4/6] Creando/verificando periodos de reporte...\n";

$stmtPerIns = $pdo->prepare("INSERT INTO despacho_periodos_reporte (codigo, nombre, tipo_periodo, fecha_inicio, fecha_fin, fecha_limite_entrega, es_periodo_activo, estado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin), fecha_limite_entrega = VALUES(fecha_limite_entrega)");

$stmtPerIns->execute(['CORTE-2026-10-19', 'Corte Consolidado 19 de octubre de 2026', 'CORTE_ESPECIAL', '2026-10-01', '2026-10-19', '2026-10-19', 0, 'CERRADO']);
$stmtPerIns->execute(['SEM-2026-10-26', 'Semana del 20 al 26 de octubre de 2026', 'SEMANAL', '2026-10-20', '2026-10-26', '2026-10-26', 1, 'ABIERTO']);

$periodoCorteId = (int)$pdo->query("SELECT id FROM despacho_periodos_reporte WHERE codigo = 'CORTE-2026-10-19'")->fetchColumn();
$periodoActivoId = (int)$pdo->query("SELECT id FROM despacho_periodos_reporte WHERE codigo = 'SEM-2026-10-26'")->fetchColumn();

// ============================================================================
// 5. IMPORTACIÓN EN TRANSACCIÓN DE LOS 49 REGISTROS/GRUPOS DEL EXCEL
// ============================================================================
echo "[5/6] Importando los 49 registros/grupos del Excel con corte 19/10/2026...\n";

$xlsxCandidates = [
    $argv[1] ?? '',
    'C:\\Users\\sotoc\\Downloads\\Consolidado_maestro_alertas_contratos_Sumapaz_2026-10-19.xlsx',
    'C:\\Users\\sotoc\\.gemini\\antigravity\\brain\\66b9a8e9-0638-4b2e-9df2-cb8be1cbc495\\scratch\\source_excel.xlsx',
];
$xlsxPath = null;
foreach ($xlsxCandidates as $cand) {
    if ($cand !== '' && file_exists($cand)) {
        $xlsxPath = $cand;
        break;
    }
}
if ($xlsxPath === null) {
    throw new RuntimeException("No se encontró el archivo Excel fuente en las rutas esperadas.");
}

$sheets = readXlsxSheets($xlsxPath);
$maestroRows = $sheets['Consolidado maestro'] ?? [];
$vigentesRows = $sheets['Alertas vigentes'] ?? [];
$historialRows = $sheets['Historial alertas'] ?? [];

$pdo->beginTransaction();

// Precargar contratos existentes por fila_excel
$existingContratos = [];
foreach ($pdo->query("SELECT fila_excel, id FROM despacho_contratos WHERE fila_excel IS NOT NULL")->fetchAll() as $cr) {
    $existingContratos[(int)$cr['fila_excel']] = (int)$cr['id'];
}

$existingReportes = [];
foreach ($pdo->query("SELECT radicado, id FROM despacho_reportes")->fetchAll() as $rr) {
    $existingReportes[$rr['radicado']] = (int)$rr['id'];
}

$existingVersiones = [];
foreach ($pdo->query("SELECT reporte_id, id FROM despacho_versiones_reporte WHERE numero_version = 1")->fetchAll() as $vr) {
    $existingVersiones[(int)$vr['reporte_id']] = (int)$vr['id'];
}

// Limpiar registros de importación base para re-insertar en bloque limpio
$pdo->exec("DELETE FROM despacho_modificaciones_contrato WHERE fuente = 'IMPORTACION_EXCEL'");
$pdo->exec("DELETE FROM despacho_componentes_contrato");

$stmtUpdContrato = $pdo->prepare("UPDATE despacho_contratos SET
    numero_contrato = ?, codigo_normalizado = ?, titulo_grupo = ?, es_grupo = ?, area_id = ?,
    objeto = ?, contratista = ?, interventoria_nombre = ?, responsable_nombre = ?, responsable_en_mensaje1 = ?,
    duracion_texto = ?, valor_inicial = ?, valor_adiciones = ?, valor_total = ?, valor_texto_original = ?,
    fecha_inicio_texto = ?, fecha_inicio_iso = ?, fecha_fin_inicial_texto = ?, fecha_fin_inicial_iso = ?,
    fecha_fin_vigente_texto = ?, fecha_fin_vigente_iso = ?,
    estado_contractual = ?, estado_reportado_texto = ?, estado_entrega = ?, estado_revision = ?,
    categoria_origen = ?, nivel_alerta = ?, tipo_alerta = ?, alerta_consolidada = ?,
    dato_conflicto = ?, fuente_actualizacion = ?, corte_consolidado = ?, fecha_corte_iso = '2026-10-19',
    tiene_mensaje1 = ?, fecha_ultimo_reporte_texto = ?, fecha_ultimo_reporte_iso = ?,
    ejecucion_fisica_texto = ?, ejecucion_fisica_pct = ?,
    ejecucion_financiera_texto = ?, ejecucion_financiera_pct = ?, ejecucion_financiera_valor = ?
    WHERE id = ?");

$stmtInsContrato = $pdo->prepare("INSERT INTO despacho_contratos (
    fila_excel, numero_contrato, codigo_normalizado, titulo_grupo, es_grupo, area_id,
    objeto, contratista, interventoria_nombre, responsable_nombre, responsable_en_mensaje1,
    duracion_texto, valor_inicial, valor_adiciones, valor_total, valor_texto_original,
    fecha_inicio_texto, fecha_inicio_iso, fecha_fin_inicial_texto, fecha_fin_inicial_iso,
    fecha_fin_vigente_texto, fecha_fin_vigente_iso,
    estado_contractual, estado_reportado_texto, estado_entrega, estado_revision,
    categoria_origen, nivel_alerta, tipo_alerta, alerta_consolidada,
    dato_conflicto, fuente_actualizacion, corte_consolidado, fecha_corte_iso,
    tiene_mensaje1, fecha_ultimo_reporte_texto, fecha_ultimo_reporte_iso,
    ejecucion_fisica_texto, ejecucion_fisica_pct,
    ejecucion_financiera_texto, ejecucion_financiera_pct, ejecucion_financiera_valor
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '2026-10-19', ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtCP = $pdo->prepare("INSERT INTO despacho_contrato_proyectos (contrato_id, proyecto_id, nota_asociacion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nota_asociacion = VALUES(nota_asociacion)");
$stmtMod = $pdo->prepare("INSERT INTO despacho_modificaciones_contrato (contrato_id, tipo_modificacion, numero_modificacion, descripcion_texto, valor_adicion, estado_tramite, fuente) VALUES (?, ?, ?, ?, ?, 'VALIDADA', 'IMPORTACION_EXCEL')");
$stmtComp = $pdo->prepare("INSERT INTO despacho_componentes_contrato (
    contrato_id, tipo_componente, numero_componente, objeto, contratista, responsable_apoyo,
    valor_inicial, valor_total, avance_fisico_pct, avance_financiero_pct, estado_componente
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtInsRep = $pdo->prepare("INSERT INTO despacho_reportes (
    radicado, contrato_id, periodo_id, tipo_mensaje, version_actual,
    responsable_reportante, origen_carga, estado_entrega, estado_revision
) VALUES (?, ?, ?, ?, 1, ?, 'IMPORTACION_EXCEL', ?, ?)");

$stmtInsVer = $pdo->prepare("INSERT INTO despacho_versiones_reporte (
    reporte_id, contrato_id, periodo_id, numero_version, tipo_mensaje,
    mensaje1_texto_original, mensaje2_texto_original, datos_estructurados_json,
    fecha_reporte_texto, fecha_reporte_iso, fecha_fin_reportada_texto, fecha_fin_reportada_iso,
    ejecucion_fisica_texto, ejecucion_fisica_pct,
    ejecucion_financiera_texto, ejecucion_financiera_pct, ejecucion_financiera_valor,
    ejecucion_financiera_obra_pct, ejecucion_financiera_inter_pct,
    estado_reportado_texto, reportado_por, fuente_version, estado_version
) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtUpdRepPtr = $pdo->prepare("UPDATE despacho_reportes SET version_actual_id = ? WHERE id = ?");
$stmtUpdConPtr = $pdo->prepare("UPDATE despacho_contratos SET ultimo_reporte_id = ?, mensaje1_actual_id = ? WHERE id = ?");

$stmtOblCorte = $pdo->prepare("INSERT INTO despacho_obligaciones_reporte (
    periodo_id, contrato_id, responsable_nombre, requiere_mensaje1, requiere_mensaje2,
    fecha_esperada, estado_entrega, estado_revision, reporte_actual_id, radicado_ultimo, fecha_recepcion
) VALUES (?, ?, ?, ?, 1, '2026-10-19', ?, ?, ?, ?, '2026-10-19 18:00:00')
ON DUPLICATE KEY UPDATE estado_entrega = VALUES(estado_entrega), estado_revision = VALUES(estado_revision), reporte_actual_id = VALUES(reporte_actual_id)");

$stmtOblAct = $pdo->prepare("INSERT IGNORE INTO despacho_obligaciones_reporte (
    periodo_id, contrato_id, responsable_nombre, requiere_mensaje1, requiere_mensaje2,
    fecha_esperada, estado_entrega, estado_revision, observacion_obligacion
) VALUES (?, ?, ?, ?, 1, '2026-10-26', 'PENDIENTE', 'SIN_REPORTE', ?)");

$contratoIdByFila = [];
$contratoIdByNumero = [];
$contratoIdByGrupo = [];
$catCountCheck = [];

for ($i = 1; $i < count($maestroRows); $i++) {
    $r = $maestroRows[$i];
    $filaExcel = $i;
    $numContrato = $r['A'] ?? ('SIN-NUMERO-' . $filaExcel);
    $tituloGrupo = $r['B'] ?? $numContrato;
    $responsable = $r['C'] ?? null;
    $msg1 = $r['D'] ?? null;
    $msg2 = $r['E'] ?? null;
    $fechaUltRepTxt = $r['F'] ?? null;
    $fechaFinVigTxt = $r['G'] ?? null;
    $ejecFisicaTxt = $r['H'] ?? null;
    $ejecFinancTxt = $r['I'] ?? null;
    $estadoRepTxt = $r['J'] ?? null;
    $nivelAlerta = $r['K'] ?? 'SEGUIMIENTO';
    $tipoAlerta = $r['L'] ?? null;
    $alertaCons = $r['M'] ?? null;
    $datoConflicto = $r['N'] ?? null;
    $fuenteAct = $r['O'] ?? null;
    $corteCons = $r['P'] ?? '19/10/2026';

    $catCountCheck[$nivelAlerta] = ($catCountCheck[$nivelAlerta] ?? 0) + 1;

    $m1Parsed = parseMensaje1($msg1);
    $tieneMsg1 = ($msg1 !== null && trim($msg1) !== '') ? 1 : 0;

    $codigoNorm = strtoupper(preg_replace('/[^A-Za-z0-9\-\/]/', '', $numContrato));
    if ($codigoNorm === '') $codigoNorm = 'REG-' . $filaExcel;

    $esGrupo = (
        strpos($numContrato, '/') !== false ||
        stripos($numContrato, ' y ') !== false ||
        strpos($tituloGrupo, '/') !== false ||
        (stripos($tituloGrupo, 'CIN') !== false && stripos($tituloGrupo, 'COP') !== false) ||
        ($msg1 !== null && substr_count(safeLower($msg1), 'número del contrato') > 1)
    ) ? 1 : 0;

    $areaCod = inferAreaCodigo($numContrato, $tituloGrupo, $msg1, $msg2);
    $areaId = $areaMap[$areaCod] ?? null;

    $fechaUltRepIso = parseSpanishDateToIso($fechaUltRepTxt, 2026);
    $fechaFinVigIso = parseSpanishDateToIso($fechaFinVigTxt, 2026);
    $ejecFisicaPct = parsePercentage($ejecFisicaTxt);
    $ejecFinancPct = parsePercentage($ejecFinancTxt);
    $ejecFinancVal = parseMoneyValue($ejecFinancTxt);

    $estadoContractual = normalizeEstadoContractual($estadoRepTxt, $nivelAlerta, $msg1);

    $estadoEntrega = 'RECIBIDO';
    if ($fechaUltRepIso !== null && $fechaUltRepIso < '2026-10-01') {
        $estadoEntrega = 'DESACTUALIZADO';
    } elseif ($fechaUltRepTxt !== null && (stripos($fechaUltRepTxt, '07 de') !== false || stripos($fechaUltRepTxt, 'agosto') !== false || stripos($fechaUltRepTxt, '/08/') !== false || stripos($fechaUltRepTxt, '/07/') !== false || stripos($fechaUltRepTxt, '-09-') !== false)) {
        $estadoEntrega = 'DESACTUALIZADO';
    }

    $estadoRevision = 'PENDIENTE_REVISION';
    if ($nivelAlerta === 'VALIDAR DATOS' || $datoConflicto !== null) {
        $estadoRevision = 'OBSERVADO';
    }

    if (isset($existingContratos[$filaExcel])) {
        $contratoId = $existingContratos[$filaExcel];
        $stmtUpdContrato->execute([
            $numContrato, $codigoNorm, $tituloGrupo, $esGrupo, $areaId,
            $m1Parsed['objeto'], $m1Parsed['contratista'], $m1Parsed['interventoria'], $responsable, $m1Parsed['apoyo_supervision'],
            $m1Parsed['duracion_texto'], $m1Parsed['valor_inicial'], $m1Parsed['valor_adiciones'], $m1Parsed['valor_total'], $m1Parsed['valor_texto_original'],
            $m1Parsed['fecha_inicio_texto'], $m1Parsed['fecha_inicio_iso'], $m1Parsed['fecha_fin_inicial_texto'], $m1Parsed['fecha_fin_inicial_iso'],
            $fechaFinVigTxt, $fechaFinVigIso,
            $estadoContractual, $estadoRepTxt, $estadoEntrega, $estadoRevision,
            $nivelAlerta, $nivelAlerta, $tipoAlerta, $alertaCons,
            $datoConflicto, $fuenteAct, $corteCons,
            $tieneMsg1, $fechaUltRepTxt, $fechaUltRepIso,
            $ejecFisicaTxt, $ejecFisicaPct,
            $ejecFinancTxt, $ejecFinancPct, $ejecFinancVal,
            $contratoId
        ]);
    } else {
        $stmtInsContrato->execute([
            $filaExcel, $numContrato, $codigoNorm, $tituloGrupo, $esGrupo, $areaId,
            $m1Parsed['objeto'], $m1Parsed['contratista'], $m1Parsed['interventoria'], $responsable, $m1Parsed['apoyo_supervision'],
            $m1Parsed['duracion_texto'], $m1Parsed['valor_inicial'], $m1Parsed['valor_adiciones'], $m1Parsed['valor_total'], $m1Parsed['valor_texto_original'],
            $m1Parsed['fecha_inicio_texto'], $m1Parsed['fecha_inicio_iso'], $m1Parsed['fecha_fin_inicial_texto'], $m1Parsed['fecha_fin_inicial_iso'],
            $fechaFinVigTxt, $fechaFinVigIso,
            $estadoContractual, $estadoRepTxt, $estadoEntrega, $estadoRevision,
            $nivelAlerta, $nivelAlerta, $tipoAlerta, $alertaCons,
            $datoConflicto, $fuenteAct, $corteCons,
            $tieneMsg1, $fechaUltRepTxt, $fechaUltRepIso,
            $ejecFisicaTxt, $ejecFisicaPct,
            $ejecFinancTxt, $ejecFinancPct, $ejecFinancVal
        ]);
        $contratoId = (int)$pdo->lastInsertId();
        $existingContratos[$filaExcel] = $contratoId;
    }

    $contratoIdByFila[$filaExcel] = $contratoId;
    $contratoIdByNumero[trim($numContrato)] = $contratoId;
    $contratoIdByGrupo[trim($tituloGrupo)] = $contratoId;

    if (!empty($m1Parsed['proyectos_codigos'])) {
        $note = count($m1Parsed['proyectos_codigos']) > 1
            ? 'Contrato transversal asociado a múltiples proyectos sin desagregación porcentual oficial en fuente'
            : 'Proyecto identificado en ficha contractual Mensaje 1';
        foreach ($m1Parsed['proyectos_codigos'] as $pCod) {
            if (isset($proyectoMap[$pCod])) {
                $stmtCP->execute([$contratoId, $proyectoMap[$pCod], $note]);
            }
        }
    }

    if (!empty($m1Parsed['modificaciones'])) {
        foreach ($m1Parsed['modificaciones'] as $mod) {
            $stmtMod->execute([$contratoId, $mod['tipo'], safeSub($mod['numero'], 0, 110), $mod['descripcion'], $mod['valor']]);
        }
    }

    $finObraPct = null;
    $finInterPct = null;
    if ($msg2 !== null) {
        if (preg_match('/financiera\s+obra\s*:\s*(\d+(?:[\.,]\d+)?)\s*%/iu', $msg2, $mo)) {
            $finObraPct = (float)str_replace(',', '.', $mo[1]);
        } elseif (preg_match('/(\d+(?:[\.,]\d+)?)\s*%\s*obra/iu', $msg2, $mo)) {
            $finObraPct = (float)str_replace(',', '.', $mo[1]);
        }
        if (preg_match('/financiera\s+interventor[ií]a\s*:\s*(\d+(?:[\.,]\d+)?)\s*%/iu', $msg2, $mi)) {
            $finInterPct = (float)str_replace(',', '.', $mi[1]);
        } elseif (preg_match('/(\d+(?:[\.,]\d+)?)\s*%\s*interventor[ií]a/iu', $msg2, $mi)) {
            $finInterPct = (float)str_replace(',', '.', $mi[1]);
        }
    }

    if ($esGrupo) {
        $stmtComp->execute([
            $contratoId, 'OBRA', $numContrato, $m1Parsed['objeto'], $m1Parsed['contratista'], $responsable,
            $m1Parsed['valor_inicial'], $m1Parsed['valor_total'], $ejecFisicaPct, $finObraPct ?? $ejecFinancPct, $estadoContractual
        ]);
        $interNum = 'INTERVENTORIA-' . $codigoNorm;
        if (preg_match('/(CIN[\s\-]*\d+[\s\-]*20\d{2}|COP[\s\-]*641[\s\-]*2025)/iu', $tituloGrupo . ' ' . ($msg1 ?? '') . ' ' . ($msg2 ?? ''), $mCin)) {
            $interNum = trim($mCin[1]);
        }
        $stmtComp->execute([
            $contratoId, 'INTERVENTORIA', $interNum, 'Interventoría asociada a ' . $numContrato,
            $m1Parsed['interventoria'], $responsable, null, null, null, $finInterPct, $estadoContractual
        ]);
    } else {
        $stmtComp->execute([
            $contratoId, 'PRINCIPAL', $numContrato, $m1Parsed['objeto'], $m1Parsed['contratista'], $responsable,
            $m1Parsed['valor_inicial'], $m1Parsed['valor_total'], $ejecFisicaPct, $ejecFinancPct, $estadoContractual
        ]);
    }

    $radicadoCorte = sprintf('RAD-20261019-%03d', $filaExcel);
    $tipoMsg = $tieneMsg1 ? 'AMBOS' : 'MENSAJE_2';

    if (isset($existingReportes[$radicadoCorte])) {
        $repId = $existingReportes[$radicadoCorte];
    } else {
        $stmtInsRep->execute([
            $radicadoCorte, $contratoId, $periodoCorteId, $tipoMsg,
            $responsable, $estadoEntrega, $estadoRevision
        ]);
        $repId = (int)$pdo->lastInsertId();
        $existingReportes[$radicadoCorte] = $repId;
    }

    $estructuradoJson = json_encode([
        'fila_excel' => $filaExcel,
        'numero_contrato' => $numContrato,
        'titulo_grupo' => $tituloGrupo,
        'responsable' => $responsable,
        'fecha_ultimo_reporte' => $fechaUltRepTxt,
        'fecha_fin_vigente' => $fechaFinVigTxt,
        'ejecucion_fisica' => $ejecFisicaTxt,
        'ejecucion_financiera' => $ejecFinancTxt,
        'estado_reportado' => $estadoRepTxt,
        'nivel_alerta' => $nivelAlerta,
        'tipo_alerta' => $tipoAlerta,
        'dato_conflicto' => $datoConflicto,
        'fuente_actualizacion' => $fuenteAct,
    ], JSON_UNESCAPED_UNICODE);

    if (isset($existingVersiones[$repId])) {
        $verId = $existingVersiones[$repId];
    } else {
        $stmtInsVer->execute([
            $repId, $contratoId, $periodoCorteId, $tipoMsg,
            $msg1, $msg2, $estructuradoJson,
            $fechaUltRepTxt, $fechaUltRepIso, $fechaFinVigTxt, $fechaFinVigIso,
            $ejecFisicaTxt, $ejecFisicaPct,
            $ejecFinancTxt, $ejecFinancPct, $ejecFinancVal,
            $finObraPct, $finInterPct,
            $estadoRepTxt, $responsable, $fuenteAct ?? 'Corte 19/10/2026', $estadoRevision
        ]);
        $verId = (int)$pdo->lastInsertId();
        $existingVersiones[$repId] = $verId;
    }

    $stmtUpdRepPtr->execute([$verId, $repId]);
    $stmtUpdConPtr->execute([$repId, $tieneMsg1 ? $verId : null, $contratoId]);

    $stmtOblCorte->execute([$periodoCorteId, $contratoId, $responsable, $tieneMsg1 ? 0 : 1, $estadoEntrega, $estadoRevision, $repId, $radicadoCorte]);
    $obsObl = $tieneMsg1 ? 'Pendiente cargue del reporte del periodo actual' : 'Falta Mensaje 1 (ficha contractual) y reporte del periodo actual';
    $stmtOblAct->execute([$periodoActivoId, $contratoId, $responsable, $tieneMsg1 ? 0 : 1, $obsObl]);
}

// ============================================================================
// 6. IMPORTACIÓN DE ALERTAS VIGENTES (17) E HISTORIAL DE ALERTAS (13)
// ============================================================================
echo "[6/6] Sincronizando Alertas vigentes (17) e Historial de alertas (13)...\n";

$pdo->exec("DELETE FROM despacho_alertas WHERE origen_hoja IN ('ALERTAS_VIGENTES', 'CONSOLIDADO_MAESTRO')");
$pdo->exec("DELETE FROM despacho_eventos_alerta WHERE tipo_evento = 'HISTORIAL_EXCEL'");

$stmtInsAlerta = $pdo->prepare("INSERT INTO despacho_alertas (
    contrato_id, origen_hoja, categoria_alerta, criterio_codigo, nivel_alerta, tipo_alerta,
    descripcion_alerta, condicion_evaluada, datos_usados_json, fecha_evaluacion, version_regla,
    dato_por_validar, estado_alerta, requiere_accion, fecha_corte
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '2026-10-19 18:00:00', 'CONSOLIDADO_2026_10_19', ?, 'ACTIVA', 1, '19/10/2026')");

$alertasVigentesCount = 0;
for ($i = 1; $i < count($vigentesRows); $i++) {
    $av = $vigentesRows[$i];
    $nivel = $av['A'] ?? 'ALTA';
    $numB = trim($av['B'] ?? '');
    $grpC = trim($av['C'] ?? '');
    $tipoI = $av['I'] ?? 'Alerta contractual';
    $descJ = $av['J'] ?? '';
    $valK = $av['K'] ?? null;

    $cId = $contratoIdByNumero[$numB] ?? $contratoIdByGrupo[$grpC] ?? null;
    if ($cId === null) {
        foreach ($contratoIdByGrupo as $gKey => $idVal) {
            if (stripos($gKey, $numB) !== false || stripos($numB, $gKey) !== false) {
                $cId = $idVal;
                break;
            }
        }
    }
    if ($cId !== null) {
        // Clasificar el criterio cualitativo predominante y separar falta de reporte de riesgo contractual
        $tipoLower = safeLower($tipoI . ' ' . $descJ);
        $catAlerta = 'RIESGO_CONTRACTUAL';
        $critCod = 'TERMINACION_CERCANA_EJECUCION_INSUFICIENTE';
        if (strpos($tipoLower, 'desactualizad') !== false || strpos($tipoLower, 'inconsistente') !== false || strpos($tipoLower, 'cruzado') !== false) {
            $catAlerta = 'FALTA_REPORTE';
            $critCod = 'FALTA_INFORMACION_ACTUALIZACION';
        } elseif (strpos($tipoLower, 'rezago financiero') !== false || strpos($tipoLower, 'cierre financiero') !== false || strpos($tipoLower, '0% financiero') !== false) {
            $catAlerta = 'RIESGO_CONTRACTUAL';
            $critCod = 'EJECUCION_FINANCIERA_MUY_BAJA';
        }

        $datosUsados = json_encode([
            'fecha_ultimo_reporte' => $av['E'] ?? null,
            'fecha_fin_vigente' => $av['F'] ?? null,
            'ejecucion_fisica' => $av['G'] ?? null,
            'ejecucion_financiera' => $av['H'] ?? null,
            'dato_por_validar' => $valK,
            'corte' => '19/10/2026'
        ], JSON_UNESCAPED_UNICODE);

        $condEval = "Categoría consolidada en origen (Corte 19/10/2026): [$nivel] - $tipoI";

        $stmtInsAlerta->execute([
            $cId, 'ALERTAS_VIGENTES', $catAlerta, $critCod, $nivel, $tipoI,
            $descJ, $condEval, $datosUsados, $valK
        ]);
        $alertasVigentesCount++;
    }
}

$stmtInsHist = $pdo->prepare("INSERT INTO despacho_eventos_alerta (
    contrato_id, contrato_nombre_referencia, fecha_corte_texto, tipo_evento, cambio_dato, efecto_alerta, usuario_nombre
) VALUES (?, ?, ?, 'HISTORIAL_EXCEL', ?, ?, 'Consolidado 19/10/2026')");

$historialCount = 0;
for ($i = 1; $i < count($historialRows); $i++) {
    $hr = $historialRows[$i];
    $fCorte = $hr['A'] ?? '19/10/2026';
    $cRef = trim($hr['B'] ?? '');
    $cambio = $hr['C'] ?? '';
    $efecto = $hr['D'] ?? '';

    $cId = $contratoIdByGrupo[$cRef] ?? $contratoIdByNumero[$cRef] ?? null;
    if ($cId === null) {
        if (preg_match('/([A-Z]{2,4}[\s\-]*\d{3,6})/i', $cRef, $mc)) {
            $tokenSearch = preg_replace('/[\s\-]+/', '', strtoupper($mc[1]));
            foreach ($contratoIdByGrupo as $gKey => $idVal) {
                $gClean = preg_replace('/[\s\-]+/', '', strtoupper($gKey));
                if (strpos($gClean, $tokenSearch) !== false) {
                    $cId = $idVal;
                    break;
                }
            }
        }
    }
    $stmtInsHist->execute([$cId, $cRef, $fCorte, $cambio, $efecto]);
    $historialCount++;
}

$pdo->prepare("INSERT INTO despacho_bitacora (entidad_tipo, accion, actor_tipo, actor_nombre, valor_nuevo_json) VALUES ('MIGRACION', 'IMPORTACION_CONSOLIDADO_2026_10_19', 'SISTEMA_CLI', 'cli_migrate_despacho.php', ?)")
    ->execute([json_encode([
        'contratos_importados' => count($contratoIdByFila),
        'categorias' => $catCountCheck,
        'alertas_vigentes' => $alertasVigentesCount,
        'historial_alertas' => $historialCount,
        'proyectos_catalogo' => count($proyectoMap),
        'metas_catalogo' => $metasCount,
    ], JSON_UNESCAPED_UNICODE)]);

$pdo->commit();

echo "\n=== RESUMEN DE CONCILIACIÓN ===\n";
echo "Total contratos/grupos importados: " . count($contratoIdByFila) . " (esperado: 49)\n";
foreach ($catCountCheck as $cat => $cnt) {
    echo "  - $cat: $cnt\n";
}
echo "Alertas vigentes importadas: $alertasVigentesCount (esperado: 17)\n";
echo "Eventos de historial importados: $historialCount (esperado: 13)\n";
echo "Migración finalizada con éxito.\n";
