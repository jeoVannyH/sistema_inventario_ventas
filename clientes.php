<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

$query = "SELECT id, nombre_completo, tipo_documento, numero_documento, telefono, 
                 tipo_cliente, limite_credito, saldo_actual, estado,
                 (limite_credito - saldo_actual) AS credito_disponible
          FROM clientes 
          ORDER BY nombre_completo ASC";
$resultado = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cartera de Clientes - Sistema de Inventario y Ventas</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
        .contenedor { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .header h2 { margin: 0; color: #2563eb; }
        .btn { padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; border: none; cursor: pointer; display: inline-block; color: white; }
        .btn-primary { background: #2563eb; }
        .btn-secondary { background: #64748b; margin-left: 8px; }
        .alerta-ok { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #22c55e; font-weight: bold; }
        .alerta-err { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-weight: bold; }
        .card-tabla { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #1e293b; color: white; }
        tr:hover { background: #f8fafc; }
        .text-right { text-align: right; }
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; color: white; }
        .bg-gris { background: #64748b; }
        .bg-info { background: #0284c7; }
        .bg-verde { background: #10b981; }
        .bg-amarillo { background: #f59e0b; color: #1e293b; }
        .bg-rojo { background: #dc2626; }
        .progress-wrap { background: #e2e8f0; border-radius: 8px; height: 16px; width: 110px; overflow: hidden; }
        .progress-bar { height: 100%; font-size: 11px; font-weight: bold; text-align: center; line-height: 16px; color: white; }
        /* Modal */
        .modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); justify-content: center; align-items: center; z-index: 999; }
        .modal-box { background: white; width: 90%; max-width: 650px; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-head { background: #2563eb; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .modal-body label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .modal-body input, .modal-body select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .modal-foot { padding: 15px 20px; background: #f8fafc; text-align: right; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="header">
        <div>
            <h2>👥 Gestión de Clientes y Créditos</h2>
            <p style="margin:4px 0 0 0; color:#64748b; font-size:13px;">Módulo de Ventas: Directorio oficial de compradores y auditoría de límites de saldo</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="document.getElementById('modalCliente').style.display='flex'">+ Registrar Nuevo Cliente</button>
            <a href="dashboard.php" class="btn btn-secondary">Volver al Panel</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])) { ?>
        <div class="alerta-ok">✓ <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php } elseif (isset($_GET['error'])) { ?>
        <div class="alerta-err">✗ <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php } ?>

    <div class="card-tabla">
        <table>
            <thead>
                <tr>
                    <th>Cliente / Empresa</th>
                    <th>Identificación</th>
                    <th>Contacto</th>
                    <th>Tipo Fiscal</th>
                    <th class="text-right">Límite Crédito</th>
                    <th class="text-right">Saldo Adeudado</th>
                    <th class="text-right">Disponible</th>
                    <th>Nivel de Uso</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $resultado->fetch_assoc()) { 
                    $pctUso = ($c['limite_credito'] > 0) ? ($c['saldo_actual'] / $c['limite_credito']) * 100 : 0;
                    $barColor = ($pctUso >= 90 || $c['estado'] === 'BLOQUEADO') ? 'bg-rojo' : (($pctUso >= 60) ? 'bg-amarillo' : 'bg-verde');
                    $estadoColor = ($c['estado'] === 'ACTIVO') ? 'bg-verde' : (($c['estado'] === 'BLOQUEADO') ? 'bg-rojo' : 'bg-gris');
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['nombre_completo']); ?></strong></td>
                    <td><span class="badge bg-gris"><?php echo $c['tipo_documento']; ?></span> <?php echo htmlspecialchars($c['numero_documento']); ?></td>
                    <td><?php echo htmlspecialchars($c['telefono'] ?? 'N/D'); ?></td>
                    <td><span class="badge bg-info"><?php echo $c['tipo_cliente']; ?></span></td>
                    <td class="text-right">$<?php echo number_format($c['limite_credito'], 2); ?></td>
                    <td class="text-right" style="color:#dc2626; font-weight:bold;">$<?php echo number_format($c['saldo_actual'], 2); ?></td>
                    <td class="text-right" style="color:#059669; font-weight:bold;">$<?php echo number_format($c['credito_disponible'], 2); ?></td>
                    <td>
                        <div class="progress-wrap">
                            <div class="progress-bar <?php echo $barColor; ?>" style="width: <?php echo min($pctUso, 100); ?>%;">
                                <?php echo round($pctUso); ?>%
                            </div>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $estadoColor; ?>"><?php echo $c['estado']; ?></span></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal-bg" id="modalCliente">
    <div class="modal-box">
        <form action="cliente_guardar.php" method="POST">
            <div class="modal-head">
                <strong>Ficha de Registro de Cliente</strong>
                <button type="button" onclick="document.getElementById('modalCliente').style.display='none'" style="background:none; border:none; color:white; font-size:18px; cursor:pointer;">✕</button>
            </div>
            <div class="modal-body">
                <div style="grid-column: span 2;">
                    <label>Nombre Completo o Razón Social *</label>
                    <input type="text" name="nombre_completo" required placeholder="Ej: Distribuidora Morazán S.A.">
                </div>
                <div>
                    <label>Tipo Fiscal</label>
                    <select name="tipo_cliente">
                        <option value="FINAL">Consumidor Final</option>
                        <option value="FISCAL">Crédito Fiscal (Empresa)</option>
                    </select>
                </div>
                <div>
                    <label>Tipo Documento</label>
                    <select name="tipo_documento">
                        <option value="DUI">DUI</option>
                        <option value="NIT">NIT</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label>Número de Documento *</label>
                    <input type="text" name="numero_documento" required placeholder="00000000-0">
                </div>
                <div>
                    <label>Límite de Crédito ($)</label>
                    <input type="number" step="0.01" name="limite_credito" value="0.00" required>
                </div>
                <div>
                    <label>Teléfono</label>
                    <input type="text" name="telefono" placeholder="2660-0000">
                </div>
                <div>
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" placeholder="cliente@empresa.com">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalCliente').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
