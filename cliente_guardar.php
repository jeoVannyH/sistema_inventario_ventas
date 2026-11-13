<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo  = trim($_POST['nombre_completo'] ?? '');
    $tipo_documento   = trim($_POST['tipo_documento'] ?? 'DUI');
    $numero_documento = trim($_POST['numero_documento'] ?? '');
    $telefono         = trim($_POST['telefono'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $tipo_cliente     = trim($_POST['tipo_cliente'] ?? 'FINAL');
    $limite_credito   = floatval($_POST['limite_credito'] ?? 0.00);

    if (empty($nombre_completo) || empty($numero_documento)) {
        header("Location: clientes.php?error=" . urlencode("El nombre y el número de documento son estrictamente obligatorios."));
        exit();
    }

    if ($limite_credito < 0) {
        header("Location: clientes.php?error=" . urlencode("El límite de crédito no puede ser negativo."));
        exit();
    }

    // Verificar documento único
    $stmt_check = $conn->prepare("SELECT id FROM clientes WHERE numero_documento = ?");
    $stmt_check->bind_param("s", $numero_documento);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: clientes.php?error=" . urlencode("Ya existe un cliente registrado con el número de documento ingresado."));
        exit();
    }
    $stmt_check->close();

    // Insertar nuevo cliente
    $sql = "INSERT INTO clientes (nombre_completo, tipo_documento, numero_documento, telefono, email, tipo_cliente, limite_credito, saldo_actual, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 'ACTIVO')";
    $stmt = $conn->prepare($sql);
    $tel_val = !empty($telefono) ? $telefono : null;
    $email_val = !empty($email) ? $email : null;
    $stmt->bind_param("ssssssd", $nombre_completo, $tipo_documento, $numero_documento, $tel_val, $email_val, $tipo_cliente, $limite_credito);

    if ($stmt->execute()) {
        header("Location: clientes.php?msg=" . urlencode("Cliente registrado exitosamente en el Módulo de Ventas."));
    } else {
        header("Location: clientes.php?error=" . urlencode("Error en base de datos: " . $conn->error));
    }
    $stmt->close();
    exit();
} else {
    header("Location: clientes.php");
    exit();
}
?>
