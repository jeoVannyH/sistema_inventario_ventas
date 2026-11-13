<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = intval($_POST['proveedor_id']);
    $usuario_id = intval($_SESSION['user_id']);
    $productos = $_POST['producto_id'];
    $cantidades = $_POST['cantidad'];
    $precios = $_POST['precio_compra'];

    $total_compra = 0;
    for ($i = 0; $i < count($productos); $i++) {
        $total_compra += intval($cantidades[$i]) * floatval($precios[$i]);
    }

    $conn->begin_transaction();
    try {
        $stmt_compra = $conn->prepare("INSERT INTO compras (proveedor_id, usuario_id, total) VALUES (?, ?, ?)");
        $stmt_compra->bind_param("iid", $proveedor_id, $usuario_id, $total_compra);
        $stmt_compra->execute();
        $compra_id = $conn->insert_id;
        $stmt_compra->close();

        $stmt_det = $conn->prepare("INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_compra) VALUES (?, ?, ?, ?)");
        $stmt_get_stock = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
        $stmt_stock = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt_kardex = $conn->prepare("INSERT INTO kardex (id_producto, id_documento, tipo_movimiento, cantidad_entrada, costo_unitario, stock_anterior, stock_actual, motivo) VALUES (?, ?, 'ENTRADA_COMPRA', ?, ?, ?, ?, 'Ingreso por Compra a Proveedor')");

        for ($i = 0; $i < count($productos); $i++) {
            $prod_id = intval($productos[$i]);
            $cant = intval($cantidades[$i]);
            $costo = floatval($precios[$i]);

            // Consultar stock anterior
            $stmt_get_stock->bind_param("i", $prod_id);
            $stmt_get_stock->execute();
            $prod_row = $stmt_get_stock->get_result()->fetch_assoc();
            $stock_anterior = intval($prod_row['stock']);
            $stock_actual = $stock_anterior + $cant;

            // Guardar detalle
            $stmt_det->bind_param("iiid", $compra_id, $prod_id, $cant, $costo);
            $stmt_det->execute();

            // Actualizar stock
            $stmt_stock->bind_param("ii", $stock_actual, $prod_id);
            $stmt_stock->execute();

            // Registrar en Kardex
            $stmt_kardex->bind_param("iiidii", $prod_id, $compra_id, $cant, $costo, $stock_anterior, $stock_actual);
            $stmt_kardex->execute();
        }

        $stmt_det->close();
        $stmt_get_stock->close();
        $stmt_stock->close();
        $stmt_kardex->close();

        $conn->commit();
        header("Location: historial_compras.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error al registrar la compra: " . $e->getMessage());
    }
}
?>
