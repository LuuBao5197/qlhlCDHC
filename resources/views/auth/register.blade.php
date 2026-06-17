<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Corona Admin</title>
    <style>
      @foreach ($resourceCoreStyles as $resourceStyle)
        {!! $resourceInlineCss($resourceStyle) !!}
      @endforeach
    </style>
    <link rel="shortcut icon" href="{{ $resourceAsset('images/favicon.png') }}" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="row w-100 m-0">
          <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-bg">
            <div class="card col-lg-4 mx-auto">
              <div class="card-body px-5 py-5">
                <h3 class="card-title text-left mb-3">Register</h3>
                <form method="POST" action="{{ route('register.perform') }}">
                  @csrf

                  @php
                    $roleLabels = [
                      \App\Models\User::ROLE_TEACHER => 'Giáo viên',
                      \App\Models\User::ROLE_DEPARTMENT_STAFF => 'Nhân viên khoa',
                      \App\Models\User::ROLE_TRAINING_OFFICE => 'Nhân viên phòng đào tạo',
                    ];
                  @endphp

                  @if ($errors->any())
                      <div class="alert alert-danger">
                          <ul class="mb-0">
                              @foreach ($errors->all() as $error)
                                  <li>{{ $error }}</li>
                              @endforeach
                          </ul>
                      </div>
                  @endif

                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control p_input" required>
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control p_input" required>
                  </div>
                  <div class="form-group">
                    <label>Vai trò đăng ký</label>
                    <select id="registerRole" name="role" class="form-control p_input" required>
                      <option value="">-- Chọn vai trò --</option>
                      @foreach($roleLabels as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}" {{ old('role') === $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                      @endforeach
                    </select>
                    <small class="text-muted">Chỉ được đăng ký: Giáo viên, Nhân viên khoa, Nhân viên phòng đào tạo.</small>
                  </div>
                  <div id="teacherCodeField" class="form-group">
                    <label>Mã giáo viên</label>
                    <input
                      id="registerTeacherCode"
                      type="text"
                      name="employee_code"
                      value="{{ old('employee_code') }}"
                      class="form-control p_input"
                      placeholder="Ví dụ: GV-0001"
                    >
                    <small class="text-muted">Bắt buộc với vai trò Giáo viên. Mỗi mã chỉ dùng cho một tài khoản.</small>
                  </div>
                  <div id="departmentField" class="form-group">
                    <label>Khoa</label>
                    <select id="registerDepartment" name="department_id" class="form-control p_input">
                      <option value="">-- Chọn khoa (bắt buộc với Giáo viên/Nhân viên khoa) --</option>
                      @foreach(($departments ?? collect()) as $department)
                        <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>
                          {{ $department->code }} - {{ $department->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control p_input" required>
                  </div>
                  <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control p_input" required>
                  </div>
                  <div class="form-group d-flex align-items-center justify-content-between">
                    <div class="form-check">
                      <label class="form-check-label">
                        <input type="checkbox" class="form-check-input"> Remember me </label>
                    </div>
                    <a href="#" class="forgot-pass">Forgot password</a>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-block enter-btn">Register</button>
                  </div>
                  {{-- <div class="d-flex">
                    <button class="btn btn-facebook col mr-2">
                      <i class="mdi mdi-facebook"></i> Facebook </button>
                    <button class="btn btn-google col">
                      <i class="mdi mdi-google-plus"></i> Google plus </button>
                  </div> --}}
                  <p class="sign-up text-center">Already have an Account? <a href="{{ route('login') }}">Log in</a></p>
                  <p class="terms">By creating an account you are accepting our <a href="#">Terms & Conditions</a></p>
                </form>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
        </div>
        <!-- row ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <script>
      @foreach ($resourceCoreScripts as $resourceScript)
        {!! $resourceInlineJs($resourceScript) !!}
      @endforeach
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var roleSelect = document.getElementById('registerRole');
        var teacherCodeWrapper = document.getElementById('teacherCodeField');
        var teacherCodeInput = document.getElementById('registerTeacherCode');
        var departmentWrapper = document.getElementById('departmentField');
        var departmentSelect = document.getElementById('registerDepartment');

        if (!roleSelect || !teacherCodeWrapper || !teacherCodeInput || !departmentWrapper || !departmentSelect) {
          return;
        }

        var rolesNeedDepartment = ['teacher', 'department_staff'];

        var syncDepartmentField = function () {
          var selectedRole = roleSelect.value;
          var isTeacher = selectedRole === 'teacher';
          var isRequired = rolesNeedDepartment.indexOf(selectedRole) !== -1;

          teacherCodeWrapper.style.display = isTeacher ? '' : 'none';
          teacherCodeInput.required = isTeacher;

          if (!isTeacher) {
            teacherCodeInput.value = '';
          }

          departmentWrapper.style.display = isRequired ? '' : 'none';
          departmentSelect.required = isRequired;

          if (!isRequired) {
            departmentSelect.value = '';
          }
        };

        roleSelect.addEventListener('change', syncDepartmentField);
        syncDepartmentField();
      });
    </script>
  </body>
</html>
