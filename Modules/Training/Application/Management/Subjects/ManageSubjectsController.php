<?php

namespace Modules\Training\Application\Management\Subjects;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageSubjectsController extends Controller
{
    public function __construct(
        private ManageSubjectsHandler $handler,
        private ImportSubjectsHandler $importHandler
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

    public function import(ImportSubjectsRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFcode,name,department_code,total_periods,status\r\n"
            . "MH-001,Toán cao cấp,KHOA-CNTT,45,active\r\n"
            . "MH-002,Nhập môn lập trình,KHOA-CNTT,60,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="subject-import-template.csv"',
        ]);
    }
}