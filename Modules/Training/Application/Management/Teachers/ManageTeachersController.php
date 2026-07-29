<?php

namespace Modules\Training\Application\Management\Teachers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageTeachersController extends Controller
{
    public function __construct(
        private ManageTeachersHandler $handler,
        private ImportTeachersHandler $importHandler
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

    public function import(ImportTeachersRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFteacher_code,name,email,department_code,status\r\n"
            . "GV-001,Nguyễn Văn A,nguyenvana@example.com,KHOA-CNTT,active\r\n"
            . "GV-002,Trần Thị B,tranthib@example.com,KHOA-DT,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teacher-import-template.csv"',
        ]);
    }
}