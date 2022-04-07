<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrintingRequest;
use App\Models\Printer;
use App\Models\Printing;
use App\Models\Status;
use Illuminate\Http\Request;
use Uspdev\Replicado\Pessoa;

class PrintingController extends Controller
{
    public function store(PrintingRequest $request)
    {
        if ($request->header('Authorization') != env('API_KEY')) {
            return response('Acesso nao autorizado', 403);
        }

        // Carregamento da impressora
        $printer = $this->loadPrinter($request->printer);

        // Registra printing
        $validated = $request->validated();
        unset($validated['printer']);
        $validated['printer_id'] = $printer->id;
        $printing = Printing::create($validated);

        if (!empty($printer->rule)) {
            // 1. Verificar se usuário está em uma categoria que permite impressão
            if (!empty($printer->rule->categories)) {
                $codpes = filter_var($request->user, FILTER_SANITIZE_NUMBER_INT);
                $vinculos = Pessoa::obterSiglasVinculosAtivos($codpes);
                $permissao = array_intersect($vinculos, $printer->rule->categories) ? true : false;
                if (!$permissao) {
                    Status::createStatus('cancelled_not_authorized', $printing);

                    return response()->json([
                        'response' => 'no',
                        'printing_id' => $printing->id,
                        'latest_status' => $printing->latest_status,
                    ]);
                }
            }

            // 2. Verifica se ultrapassou da quota disponível ou não
            $quota_period = $printer->rule->quota_period;

            if (!empty($quota_period)) {
                $quantities = Printing::getPrintingsQuantities($request->user, $printer, $quota_period);
                $out_of_quota = $quantities + $request->pages * $request->copies > $printer->rule->quota;

                if ($out_of_quota) {
                    Status::createStatus('cancelled_user_out_of_quota', $printing);

                    return response()->json([
                        'response' => 'no',
                        'printing_id' => $printing->id,
                        'latest_status' => $printing->latest_status,
                        'quantities' => $quantities,
                    ]);
                }
            }

            // 3. Verifica se a impressora tem controle de fila
            if ($printer->rule->queue_control) {
                Status::createStatus('waiting_job_authorization', $printing);

                return response()->json([
                    'response' => 'no',
                    'printing_id' => $printing->id,
                    'latest_status' => $printing->latest_status,
                ]);
            }
        }

        // 4. Se a impressora não tem regra, então qualquer impressão esta liberada
        Status::createStatus('sent_to_printer_queue', $printing);

        return response()->json([
            'response' => 'yes',
            'printing_id' => $printing->id,
            'latest_status' => $printing->latest_status,
        ]);
    }

    public function showStatus(Request $request, Printing $printing)
    {
        if ($request->header('Authorization') != env('API_KEY')) {
            return response('Acesso nao autorizado', 403);
        }

        return response()->json(['latest_status' => $printing->latest_status]);
    }

    public function updateStatus(Request $request, Printing $printing)
    {
        if ($request->header('Authorization') != env('API_KEY')) {
            return response('Acesso nao autorizado', 403);
        }

        $status = $request->status;
        Status::createStatus($status, $printing);

        return response()->json(['latest_status' => $printing->latest_status]);
    }

    public function getFromJobId(Request $request)
    {
        $printer = $this->loadPrinter($request->printer);
        $printing = $printer->printings()->where('jobid', $request->jobid)->first();

        if ($printing) {
            return response()->json(['printing_id' => $printing->id]);
        } else {
            return response()->json(['printing_id' => 'Not found']);
        }
    }

    /************* Métodos privados auxiliares ***************/

    private function loadPrinter($request_printer)
    {
        $printer = Printer::where('machine_name', $request_printer)->first();
        if (!$printer) {
            $printer = new Printer();
            $printer->machine_name = $request_printer;
            $printer->name = $request_printer;
            $printer->save();
        }

        return $printer;
    }
}
