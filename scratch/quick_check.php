<?php
$h = file_get_contents('http://localhost:8000/api/download_pmapc_pdf.php?id=33');
echo "PDF Length ID 33: " . strlen($h) . " bytes.\n";
echo "Has Papas Nativas? " . (strpos($h, 'Papas Nativas') !== false ? 'YES' : 'NO') . "\n";
echo "Has José Aquino Muñoz? " . (strpos($h, 'Aquino') !== false ? 'YES' : 'NO') . "\n";
