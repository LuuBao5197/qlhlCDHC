<?php

namespace Modules\Training\Application\Management\Departments;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageDepartmentsController extends Controller
{
    public function __construct(
        private ManageDepartmentsHandler $handler,
        private ImportDepartmentsHandler $importHandler
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

    public function import(ImportDepartmentsRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFcode,name,description,status\r\n"
            . "KHOA-CNTT,Khoa Công nghệ thông tin,Đào tạo các chuyên ngành công nghệ thông tin,active\r\n"
            . "KHOA-DT,Khoa Điện tử viễn thông,,active\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="department-import-template.csv"',
        ]);
    }
}