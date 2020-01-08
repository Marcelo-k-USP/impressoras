<?php

namespace App\Http\Controllers;

use App\Printing;
use App\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Gate;
use App\Rules\Numeros_USP;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Uspdev\Replicado\Pessoa;

class PrintingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['check']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        # printings
        $user = \Auth::user();
        $printings = Printing::where('user', '=', $user->codpes);
        $quantidades = $this->quantidades($user->codpes, 'user');

        $printings = $printings->orderBy('jobid','DESC')->paginate(10);

        return view('printings/index', compact('printings','quantidades'));
    }

    public function admin(Request $request)
    {
        $this->authorize('admin');
        $printings =  Printing::orderBy('jobid','DESC')->paginate(10);
        $quantidades = $this->quantidades();
        return view('printings/index', compact('printings','quantidades'));
    }

    public function printer($printer) {
        $this->authorize('admin');
        $printings = Printing::where('printer', '=', $printer);
        $printings = $printings->orderBy('jobid','DESC')->paginate(10);
        $quantidades = $this->quantidades($printer, 'printer');
        return view('printings/index', compact('printings','quantidades'));
    }

    /*type dever ser null, user ou printer */
    private function quantidades($filter=null, $type=null){
        $quantidades = [];

        if($type === null){
            $quantidades['total'] = Printing::where('status','=','Impresso')->sum('pages');

            $quantidades['hoje'] = Printing::where('status','=','Impresso')->whereDate('created_at', Carbon::today())->sum('pages');
            $quantidades['mes'] = Printing::where('status','=','Impresso')->whereMonth('created_at','=' , date('n'))->sum('pages');
        } else {

            $quantidades['total'] = Printing::where('status','=','Impresso')->where($type, '=', $filter)->sum('pages');

            $quantidades['hoje'] = Printing::where('status','=','Impresso')->where($type, '=', $filter)
                                ->whereDate('created_at', Carbon::today())
                                ->sum('pages');
            $quantidades['mes'] = Printing::where('status','=','Impresso')->where($type, '=', $filter)
                                ->whereMonth('created_at','=' , date('n'))
                                ->sum('pages');
        }
        return $quantidades;
    }

    public function pagesToday($user) {
        $user = (int) $user;
        $quantidades = $this->quantidades($user, 'user');
        return $quantidades['hoje'];
    }

    public function check($user, $printer ,int $pages) {
        /* Manualmente vamos implementar controle para alunos apenas
         * Essas regras irão para interface futuramente para ficarem mais flexíveis
         */

        /* impressoras sem controle de quota */
        $noquota = ['fcs_samsung_cor_x7500lx_dcp'];
        if (in_array(trim($printer), $noquota)) {
            return "sim";
        }

        if (strpos($user, 'lab') !== false) {
            return 'nao';
        }

        /* Nesta fase só queremos codpes*/
        $user = (int) $user;
        $quantidades = $this->quantidades($user, 'user');

        $vinculos = Pessoa::vinculosSiglas($user,8);
        /* Regra 0 - libera para funcionário, estagiários e docentes*/
        foreach($vinculos as $vinculo){
            if (trim($vinculo) == 'ESTAGIARIORH' || trim($vinculo) == 'SERVIDOR' || trim($vinculo) == 'ALUNOPD') {
                return 'sim';
            }
        }
        foreach($vinculos as $vinculo){
            /* regra 1: ALUNOGR pode imprimir 30 páginas por dia */
            if ($vinculo == 'ALUNOGR') {
                if($pages + $quantidades['hoje'] > 30)
                    return 'nao';
            }

            /* regra 2: ALUNOPOS pode imprimir 100 páginas por mês */
            if($vinculo == 'ALUNOPOS') {
                if($pages + $quantidades['mes'] > 100)
                    return 'nao';
            }
        }
        return 'sim';
    }
}
