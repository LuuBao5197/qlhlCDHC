<?php

namespace Modules\Training\Application\Management\TrainingPrograms;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageTrainingProgramsController extends Controller
{
    public function __construct(
        private ManageTrainingProgramsHandler $handler,
        private ImportTrainingProgramsHandler $importHandler
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

    public function import(ImportTrainingProgramsRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFcode,name,status\r\n"
            . "CTĐT-01,Chương trình đào tạo Trung cấp,active\r\n"
            . "CTĐT-02,Chương trình đào tạo Cao đẳng,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="training-program-import-template.csv"',
        ]);
    }
}
