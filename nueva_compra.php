<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

$proveedores = $conn->query("SELECT id, nombre_empresa FROM proveedores ORDER BY nombre_empresa ASC");
$productos = $conn->query("SELECT id, nombre_producto, precio, stock FROM productos ORDER BY nombre_producto ASC");
$lista_productos = [];
while ($p = $productos->fetch_assoc()) {
    $lista_productos[] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nueva Compra</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 20px; }
        .container { max-width: 850px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .btn-volver { background: #64748b; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #334155; }
        select, input { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .fila-item { display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: center; }
        .btn-agregar { background: #3b82f6; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-bottom: 20px; }
        .btn-guardar { background: #10b981; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; }
        .btn-quitar { background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;">🛒 Registrar Nueva Compra</h2>
        <a href="dashboard.php" class="btn-volver">Volver al Dashboard</a>
    </div>

    <form action="guardar_compra.php" method="POST">
        <div class="form-group">
            <label>Seleccione el Proveedor:</label>
            <select name="proveedor_id" required>
                <option value="">-- Elija un proveedor --</option>
                <?php while ($prov = $proveedores->fetch_assoc()): ?>
                    <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre_empresa']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <label>Artículos de la Factura:</label>
        <div id="contenedor-items">
            <div class="fila-item">
                <select name="producto_id[]" required>
                    <option value="">-- Seleccione producto --</option>
                    <?php foreach ($lista_productos as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['nombre_producto']); ?> (Stock: <?php echo $prod['stock']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="cantidad[]" min="1" placeholder="Cantidad" required>
                <input type="number" name="precio_compra[]" step="0.01" min="0.01" placeholder="Costo Unit. ($)" required>
                <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">X</button>
            </div>
        </div>

        <button type="button" class="btn-agregar" onclick="agregarFila()">+ Agregar otro producto</button>
        <button type="submit" class="btn-guardar">💾 Guardar Compra y Sumar Stock</button>
    </form>
</div>

<script>
function agregarFila() {
    const contenedor = document.getElementById('contenedor-items');
    const primeraFila = contenedor.querySelector('.fila-item');
    const nuevaFila = primeraFila.cloneNode(true);
    nuevaFila.querySelectorAll('input').forEach(input => input.value = '');
    nuevaFila.querySelector('select').selectedIndex = 0;
    contenedor.appendChild(nuevaFila);
}
</script>
</body>
</html>
