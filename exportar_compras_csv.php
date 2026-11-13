<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_consolidado_compras_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID FACTURA', 'FECHA EMISION', 'PROVEEDOR', 'USUARIO REGISTRO', 'TOTAL FACTURA ($)', 'ESTADO']);

$sql = "SELECT c.id, c.fecha, p.nombre_empresa, u.nombre_completo AS usuario, c.total, c.estado 
        FROM compras c 
        INNER JOIN proveedores p ON c.proveedor_id = p.id 
        INNER JOIN usuarios u ON c.usuario_id = u.id 
        ORDER BY c.id DESC";
$resultado = $conn->query($sql);

while ($fila = $resultado->fetch_assoc()) {
    fputcsv($output, [
        '#' . str_pad($fila['id'], 6, '0', STR_PAD_LEFT),
        date('d/m/Y H:i', strtotime($fila['fecha'])),
        $fila['nombre_empresa'],
        $fila['usuario'],
        number_format($fila['total'], 2),
        $fila['estado']
    ]);
}
fclose($output);
exit();
?>
