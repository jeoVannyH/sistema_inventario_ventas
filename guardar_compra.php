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

    // Calcular total general de la compra
    $total_compra = 0;
    for ($i = 0; $i < count($productos); $i++) {
        $total_compra += intval($cantidades[$i]) * floatval($precios[$i]);
    }

    $conn->begin_transaction();
    try {
        // 1. Insertar cabecera de compra
        $sql_compra = "INSERT INTO compras (proveedor_id, usuario_id, total) VALUES (?, ?, ?)";
        $stmt_compra = $conn->prepare($sql_compra);
        $stmt_compra->bind_param("iid", $proveedor_id, $usuario_id, $total_compra);
        $stmt_compra->execute();
        $compra_id = $conn->insert_id;
        $stmt_compra->close();

        // 2. Insertar detalles y sumar stock en SQL (Guía 25)
        $sql_det = "INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_compra) VALUES (?, ?, ?, ?)";
        $stmt_det = $conn->prepare($sql_det);

        $sql_stock = "UPDATE productos SET stock = stock + ? WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock);

        for ($i = 0; $i < count($productos); $i++) {
            $prod_id = intval($productos[$i]);
            $cant = intval($cantidades[$i]);
            $costo = floatval($precios[$i]);

            $stmt_det->bind_param("iiid", $compra_id, $prod_id, $cant, $costo);
            $stmt_det->execute();

            $stmt_stock->bind_param("ii", $cant, $prod_id);
            $stmt_stock->execute();
        }

        $stmt_det->close();
        $stmt_stock->close();

        $conn->commit();
        header("Location: historial_compras.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error al registrar la compra: " . $e->getMessage());
    }
}
?>
