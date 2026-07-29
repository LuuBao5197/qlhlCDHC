<?php

namespace Modules\Training\Application\Management\TrainingBatches;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageTrainingBatchesController extends Controller
{
    public function __construct(
        private ManageTrainingBatchesHandler $handler,
        private ImportTrainingBatchesHandler $importHandler
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->handler->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->handler->store($request);
    }

    public function show(int $id): JsonResponse
    {
        return $this->handler->show($id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->handler->update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->handler->destroy($id);
    }

    public function import(ImportTrainingBatchesRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFtraining_program_code,code,name,status\r\n"
            . "CTĐT-01,K26A,Khóa 26A,active\r\n"
            . "CTĐT-01,K26B,Khóa 26B,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="training-batch-import-template.csv"',
        ]);
    }
}
