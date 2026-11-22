<?php
/**
 * ---------------------------------------------------------------------
 * controllers/InicioController.php
 * ---------------------------------------------------------------------
 * Panel principal:
 *   - Empleado: ventas de hoy, encargos que se entregan hoy y stock bajo.
 *   - Administrador: lo mismo + finanzas del mes, gráfica y más vendidos.
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class InicioController extends Controller
{
    public function index(): void
    {
        $this->soloRoles();

        [$ventasN, $ventasTotal] = Pedido::ventasHoy();
        $datos = [
            'titulo'      => 'Panel',
            'ventasN'     => $ventasN,
            'ventasTotal' => $ventasTotal,
            'porEstado'   => Pedido::conteoPorEstado(),
            'encargosHoy' => Pedido::encargosDeHoy(),
            'stockBajo'   => Producto::stockBajo(),
        ];

        if (Auth::es(Auth::ADMIN)) {
            $datos += [
                'resumen'     => Finanza::resumen(date('Y-m')),
                'serie'       => Finanza::porDia(7),
                'masVendidos' => Producto::masVendidos(),
                'clientes'    => Cliente::total(),
            ];
            $this->view('inicio/admin', $datos);
            return;
        }
        $this->view('inicio/empleado', $datos);
    }
}
