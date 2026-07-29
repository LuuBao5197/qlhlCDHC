<?php

namespace Modules\Training\Application\Management\TrainingClasses;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageTrainingClassesController extends Controller
{
    public function __construct(
        private ManageTrainingClassesHandler $handler,
        private ImportTrainingClassesHandler $importHandler
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

    public function import(ImportTrainingClassesRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFcode,name,training_batch_code,course_year,total_students,default_room_code,status\r\n"
            . "L01,Lớp 01,K26A,2026,30,,active\r\n"
            . "L02,Lớp 02,K26A,2026,25,,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="training-class-import-template.csv"',
        ]);
    }
}