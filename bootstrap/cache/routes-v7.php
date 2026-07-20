<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/reports/teaching' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.teaching.overview',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reports/teaching/teachers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.teaching.teachers',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reports/teaching/quality' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.teaching.quality',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reports/teaching/classes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.teaching.classes',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reports/teaching/departments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.teaching.departments',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Jo2NErJXilV2M6eH',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::xOzMjEpqQRUzNci3',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'login.perform',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/forgot-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.request',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'password.email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reset-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.reset',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'password.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/account/activate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'account.activate.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'account.activate.submit',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/home' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Ahk0oKeyJNY37XFo',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/settings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'settings',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/account/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'account.profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/account/password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'account.password.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/users' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.users.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/schedules' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.schedule.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'api.schedule.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/schedules' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/schedules/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/schedules/import-template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.import-template',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/department-monthly-assignment-batches' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'department-monthly-assignment-batches.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/teaching-support-requests' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/teaching-support-requests/inbox' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.inbox',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/change-requests/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'change-request.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/change-requests' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'change-request.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/change-requests/holiday-reschedule' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'change-request.holiday-reschedule.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/holiday-calendars' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'holiday-calendar.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/monthly-schedules/initialize' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.initialize.form',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.initialize',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/schedule' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::cul61H57ZngLijPA',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/teacher-accounts/activate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teacher-accounts.activate.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'teacher-accounts.activate.submit',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/training-programs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-programs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-programs.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/training-batches' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-batches.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-batches.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/departments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.departments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.departments.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/training-classes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-classes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-classes.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/teachers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.teachers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.teachers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/rooms' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.rooms.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.rooms.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/subjects' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.subjects.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.subjects.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/subject-lessons' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.subject-lessons.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.subject-lessons.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/students/import-template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.import-template',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/students/import' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.import',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/management/students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/duty-log' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'duty-log.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/duty-log/edit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'duty-log.edit',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/duty-log/submit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'duty-log.submit',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/teacher-slot-evaluations' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teacher-slot-evaluations.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/teacher-slot-evaluations/submit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teacher-slot-evaluations.submit',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/notifications/(?|([^/]++)(*:33)|mark\\-all\\-read(*:55))|/a(?|dmin/users/([^/]++)/(?|resend\\-invitation(*:109)|lock(*:121)|unlock(*:135)|position(*:151))|pi/v1/schedules/([^/]++)(?|(*:187)))|/s(?|chedules/([^/]++)(?|(*:222)|/(?|edit(*:238)|s(?|ubmit(*:255)|emester\\-events(?|(*:281)|/([^/]++)(?|(*:301))))|leadership\\-review(*:330))|(*:339))|emester(?|(?:/([^/]++)(?:/([^/]++)(?:/([^/]++))?)?)?(*:400)|\\-schedule(?:/([^/]++)(?:/([^/]++)(?:/([^/]++))?)?)?(*:460))|torage/(.*)(?|(*:483)))|/m(?|onthly\\-schedules/([^/]++)/(?|assignment(?|(*:541))|slot(?|s/([^/]++)/merge(?|\\-candidates(*:588)|(*:596))|\\-groups/([^/]++)/split(*:628))|department\\-monthly\\-assignment\\-batches/submit(*:684)|teaching\\-support\\-requests(?|(*:722)|/modal(*:736)))|anagement/(?|t(?|raining\\-(?|programs/([^/]++)(?|(*:795))|batches/([^/]++)(?|(*:823))|classes/([^/]++)(?|(*:851)))|eachers/([^/]++)(?|(*:880)))|departments/([^/]++)(?|(*:913))|rooms/([^/]++)(?|(*:939))|s(?|ubject(?|s/([^/]++)(?|(*:974))|\\-lessons/([^/]++)(?|(*:1004)))|tudents/([^/]++)(?|(*:1034)))))|/department\\-monthly\\-assignment\\-batches/([^/]++)(?|(*:1100)|/(?|approve(*:1120)|return(*:1135)))|/teaching\\-support\\-(?|request(?|s/([^/]++)(?|(*:1192)|/(?|review(*:1211)|withdraw(*:1228)|c(?|onfirm(*:1247)|hange\\-requests(?|/create(*:1281)|(*:1290)))))|\\-items/([^/]++)/assign(*:1326))|change\\-requests/([^/]++)(?|(*:1364)|/review(*:1380)))|/change\\-requests/([^/]++)/review(*:1424)|/holiday\\-calendars/([^/]++)(?|(*:1464)))/?$}sDu',
    ),
    3 => 
    array (
      33 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.show',
          ),
          1 => 
          array (
            0 => 'notification',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      55 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.read-all',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      109 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.users.resend-invitation',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      121 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.users.lock',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      135 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.users.unlock',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      151 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.users.update-position',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      187 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.schedule.show',
          ),
          1 => 
          array (
            0 => 'schedule',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'api.schedule.update',
          ),
          1 => 
          array (
            0 => 'schedule',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'api.schedule.destroy',
          ),
          1 => 
          array (
            0 => 'schedule',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      222 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      238 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      255 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.submit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      281 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester-events.index',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester-events.store',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      301 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester-events.update',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'eventId',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester-events.destroy',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'eventId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      330 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.leadership-review',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      339 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      400 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester',
            'semester' => NULL,
            'year' => NULL,
            'className' => NULL,
          ),
          1 => 
          array (
            0 => 'semester',
            1 => 'year',
            2 => 'className',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      460 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'schedule.semester.public',
            'semester' => NULL,
            'year' => NULL,
            'className' => NULL,
          ),
          1 => 
          array (
            0 => 'semester',
            1 => 'year',
            2 => 'className',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      483 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local.upload',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      541 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.assignment',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.assignment.save',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      588 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.assignment.merge-candidates',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'slotId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      596 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.assignment.merge',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'slotId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      628 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.assignment.split',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'groupId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      684 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'department-monthly-assignment-batches.submit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      722 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.store',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      736 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'monthly-schedule.teaching-support-requests.modal',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      795 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-programs.show',
          ),
          1 => 
          array (
            0 => 'training_program',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-programs.update',
          ),
          1 => 
          array (
            0 => 'training_program',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-programs.destroy',
          ),
          1 => 
          array (
            0 => 'training_program',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      823 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-batches.show',
          ),
          1 => 
          array (
            0 => 'training_batch',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-batches.update',
          ),
          1 => 
          array (
            0 => 'training_batch',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-batches.destroy',
          ),
          1 => 
          array (
            0 => 'training_batch',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      851 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-classes.show',
          ),
          1 => 
          array (
            0 => 'training_class',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-classes.update',
          ),
          1 => 
          array (
            0 => 'training_class',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.training-classes.destroy',
          ),
          1 => 
          array (
            0 => 'training_class',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      880 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.teachers.show',
          ),
          1 => 
          array (
            0 => 'teacher',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.teachers.update',
          ),
          1 => 
          array (
            0 => 'teacher',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.teachers.destroy',
          ),
          1 => 
          array (
            0 => 'teacher',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      913 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.departments.show',
          ),
          1 => 
          array (
            0 => 'department',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.departments.update',
          ),
          1 => 
          array (
            0 => 'department',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.departments.destroy',
          ),
          1 => 
          array (
            0 => 'department',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      939 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.rooms.show',
          ),
          1 => 
          array (
            0 => 'room',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.rooms.update',
          ),
          1 => 
          array (
            0 => 'room',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.rooms.destroy',
          ),
          1 => 
          array (
            0 => 'room',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      974 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.subjects.show',
          ),
          1 => 
          array (
            0 => 'subject',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.subjects.update',
          ),
          1 => 
          array (
            0 => 'subject',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.subjects.destroy',
          ),
          1 => 
          array (
            0 => 'subject',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1004 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.subject-lessons.show',
          ),
          1 => 
          array (
            0 => 'subject_lesson',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.subject-lessons.update',
          ),
          1 => 
          array (
            0 => 'subject_lesson',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.subject-lessons.destroy',
          ),
          1 => 
          array (
            0 => 'subject_lesson',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1034 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.show',
          ),
          1 => 
          array (
            0 => 'student',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.update',
          ),
          1 => 
          array (
            0 => 'student',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'management.students.destroy',
          ),
          1 => 
          array (
            0 => 'student',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1100 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'department-monthly-assignment-batches.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1120 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'department-monthly-assignment-batches.approve',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1135 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'department-monthly-assignment-batches.return',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1192 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1211 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.review',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1228 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.withdraw',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1247 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-requests.confirm',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1281 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-change-requests.create',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1290 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-change-requests.store',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1326 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-request-items.assign',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1364 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-change-requests.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1380 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'teaching-support-change-requests.review',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1424 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'change-request.review',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1464 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'holiday-calendar.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'holiday-calendar.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'reports.teaching.overview' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reports/teaching',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@overview',
        'controller' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@overview',
        'as' => 'reports.teaching.overview',
        'namespace' => NULL,
        'prefix' => '/reports/teaching',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.teaching.teachers' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reports/teaching/teachers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@teachers',
        'controller' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@teachers',
        'as' => 'reports.teaching.teachers',
        'namespace' => NULL,
        'prefix' => '/reports/teaching',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.teaching.quality' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reports/teaching/quality',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@quality',
        'controller' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@quality',
        'as' => 'reports.teaching.quality',
        'namespace' => NULL,
        'prefix' => '/reports/teaching',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.teaching.classes' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reports/teaching/classes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@classes',
        'controller' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@classes',
        'as' => 'reports.teaching.classes',
        'namespace' => NULL,
        'prefix' => '/reports/teaching',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.teaching.departments' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reports/teaching/departments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@departments',
        'controller' => 'Modules\\Reports\\Application\\TeachingStatistics\\TeachingStatisticsController@departments',
        'as' => 'reports.teaching.departments',
        'namespace' => NULL,
        'prefix' => '/reports/teaching',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Jo2NErJXilV2M6eH' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:822:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'E:\\\\Em trai\\\\qlhlCDHC\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"0000000000000b830000000000000000";}}',
        'as' => 'generated::Jo2NErJXilV2M6eH',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::xOzMjEpqQRUzNci3' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:48:"function () {
    return \\view(\'index.index\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b850000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::xOzMjEpqQRUzNci3',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@showLoginForm',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@showLoginForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login.perform' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@login',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@login',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login.perform',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@logout',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@logout',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.request' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ForgotPassword\\ShowForgotPasswordController@__invoke',
        'controller' => 'App\\Application\\Auth\\ForgotPassword\\ShowForgotPasswordController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.request',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ForgotPassword\\ForgotPasswordController@__invoke',
        'controller' => 'App\\Application\\Auth\\ForgotPassword\\ForgotPasswordController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.email',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.reset' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reset-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ResetPassword\\ShowResetPasswordController@__invoke',
        'controller' => 'App\\Application\\Auth\\ResetPassword\\ShowResetPasswordController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.reset',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'reset-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ResetPassword\\ResetPasswordController@__invoke',
        'controller' => 'App\\Application\\Auth\\ResetPassword\\ResetPasswordController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'account.activate.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'account/activate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ActivateAccount\\ShowActivateAccountController@__invoke',
        'controller' => 'App\\Application\\Auth\\ActivateAccount\\ShowActivateAccountController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'account.activate.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'account.activate.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'account/activate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ActivateAccount\\ActivateAccountController@__invoke',
        'controller' => 'App\\Application\\Auth\\ActivateAccount\\ActivateAccountController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'account.activate.submit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Ahk0oKeyJNY37XFo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'home',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:320:"function () {
    $user = \\request()->user();

    if ($user->isTrainingOffice() || $user->isAdmin()) {
        return \\redirect()->route(\'duty-log.index\');
    }

    if ($user->isTeacher()) {
        return \\redirect()->route(\'teacher-slot-evaluations.index\');
    }

    return \\redirect()->route(\'schedule.index\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b1a0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::Ahk0oKeyJNY37XFo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'settings' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'settings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:59:"function () {
        return \\view(\'settings.index\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b290000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'settings',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'account.profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'account/profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Application\\Account\\UpdateProfile\\UpdateProfileController@__invoke',
        'controller' => 'App\\Application\\Account\\UpdateProfile\\UpdateProfileController',
        'as' => 'account.profile.update',
        'namespace' => NULL,
        'prefix' => '/account',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'account.password.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'account/password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Application\\Account\\ChangePassword\\ChangePasswordController@__invoke',
        'controller' => 'App\\Application\\Account\\ChangePassword\\ChangePasswordController',
        'as' => 'account.password.update',
        'namespace' => NULL,
        'prefix' => '/account',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'notifications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@index',
        'controller' => 'App\\Http\\Controllers\\NotificationController@index',
        'as' => 'notifications.index',
        'namespace' => NULL,
        'prefix' => '/notifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'notifications/{notification}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@show',
        'controller' => 'App\\Http\\Controllers\\NotificationController@show',
        'as' => 'notifications.show',
        'namespace' => NULL,
        'prefix' => '/notifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.read-all' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'notifications/mark-all-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@markAllAsRead',
        'controller' => 'App\\Http\\Controllers\\NotificationController@markAllAsRead',
        'as' => 'notifications.read-all',
        'namespace' => NULL,
        'prefix' => '/notifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'uses' => '\\App\\Http\\Controllers\\ManagementController@index',
        'controller' => '\\App\\Http\\Controllers\\ManagementController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'management.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@index',
        'as' => 'admin.index',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.users.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/users',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@store',
        'as' => 'admin.users.store',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.users.resend-invitation' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/users/{user}/resend-invitation',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@resendInvitation',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@resendInvitation',
        'as' => 'admin.users.resend-invitation',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.users.lock' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/users/{user}/lock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@lock',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@lock',
        'as' => 'admin.users.lock',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.users.unlock' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/users/{user}/unlock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@unlock',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@unlock',
        'as' => 'admin.users.unlock',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.users.update-position' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/users/{user}/position',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminController@updatePosition',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminController@updatePosition',
        'as' => 'admin.users.update-position',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.schedule.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/schedules',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'as' => 'api.schedule.index',
        'uses' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@index',
        'controller' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.schedule.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/schedules',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'as' => 'api.schedule.store',
        'uses' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@store',
        'controller' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.schedule.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/schedules/{schedule}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'as' => 'api.schedule.show',
        'uses' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@show',
        'controller' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.schedule.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/v1/schedules/{schedule}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'as' => 'api.schedule.update',
        'uses' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@update',
        'controller' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.schedule.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/schedules/{schedule}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'as' => 'api.schedule.destroy',
        'uses' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@destroy',
        'controller' => 'Modules\\Schedule\\Http\\Controllers\\ScheduleController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\GetSchedule\\GetScheduleController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\GetSchedule\\GetScheduleController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController@showForm',
        'controller' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController@showForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.import-template' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules/import-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController@downloadImportTemplate',
        'controller' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController@downloadImportTemplate',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.import-template',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'schedules',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\CreateScheduleSemester\\CreateScheduleSemesterController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\GetSchedule\\GetScheduleController@show',
        'controller' => 'Modules\\Schedule\\Application\\GetSchedule\\GetScheduleController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\UpdateScheduleSemester\\UpdateScheduleSemesterController@showForm',
        'controller' => 'Modules\\Schedule\\Application\\UpdateScheduleSemester\\UpdateScheduleSemesterController@showForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'schedules/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\UpdateScheduleSemester\\UpdateScheduleSemesterController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\UpdateScheduleSemester\\UpdateScheduleSemesterController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'schedules/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DeleteScheduleSemester\\DeleteScheduleSemesterController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\DeleteScheduleSemester\\DeleteScheduleSemesterController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'schedules/{id}/submit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\SubmitSemesterPlan\\SubmitSemesterPlanController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\SubmitSemesterPlan\\SubmitSemesterPlanController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.submit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.leadership-review' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'schedules/{id}/leadership-review',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\LeadershipReviewSemesterPlan\\LeadershipReviewSemesterPlanController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\LeadershipReviewSemesterPlan\\LeadershipReviewSemesterPlanController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.leadership-review',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.assignment' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'monthly-schedules/{id}/assignment',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleController@showForm',
        'controller' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleController@showForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.assignment',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.assignment.save' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'monthly-schedules/{id}/assignment',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.assignment.save',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.assignment.merge-candidates' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'monthly-schedules/{id}/slots/{slotId}/merge-candidates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@mergeCandidates',
        'controller' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@mergeCandidates',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.assignment.merge-candidates',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.assignment.merge' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'monthly-schedules/{id}/slots/{slotId}/merge',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@merge',
        'controller' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@merge',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.assignment.merge',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.assignment.split' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'monthly-schedules/{id}/slot-groups/{groupId}/split',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@split',
        'controller' => 'Modules\\Schedule\\Application\\AssignMonthlySchedule\\AssignMonthlyScheduleMergeController@split',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.assignment.split',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'department-monthly-assignment-batches.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'monthly-schedules/{id}/department-monthly-assignment-batches/submit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@submit',
        'controller' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@submit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'department-monthly-assignment-batches.submit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'monthly-schedules/{id}/teaching-support-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@store',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.teaching-support-requests.modal' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'monthly-schedules/{id}/teaching-support-requests/modal',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@monthlyAssignmentModal',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@monthlyAssignmentModal',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.teaching-support-requests.modal',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'department-monthly-assignment-batches.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'department-monthly-assignment-batches',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@index',
        'controller' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'department-monthly-assignment-batches.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'department-monthly-assignment-batches.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'department-monthly-assignment-batches/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@show',
        'controller' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'department-monthly-assignment-batches.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'department-monthly-assignment-batches.approve' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'department-monthly-assignment-batches/{id}/approve',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchReviewController@approve',
        'controller' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchReviewController@approve',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'department-monthly-assignment-batches.approve',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'department-monthly-assignment-batches.return' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'department-monthly-assignment-batches/{id}/return',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchReviewController@returnBatch',
        'controller' => 'Modules\\Schedule\\Application\\DepartmentMonthlyAssignmentBatch\\DepartmentMonthlyAssignmentBatchReviewController@returnBatch',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'department-monthly-assignment-batches.return',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teaching-support-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@index',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.inbox' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teaching-support-requests/inbox',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@inbox',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@inbox',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.inbox',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teaching-support-requests/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@show',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.review' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-requests/{id}/review',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@review',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@review',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.review',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.withdraw' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-requests/{id}/withdraw',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@withdraw',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@withdraw',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.withdraw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-requests.confirm' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-requests/{id}/confirm',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@confirm',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@confirm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-requests.confirm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-request-items.assign' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-request-items/{id}/assign',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@assignItem',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportRequest\\TeachingSupportRequestController@assignItem',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-request-items.assign',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-change-requests.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teaching-support-requests/{id}/change-requests/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@create',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-change-requests.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-change-requests.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-requests/{id}/change-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@store',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-change-requests.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-change-requests.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teaching-support-change-requests/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@show',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-change-requests.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teaching-support-change-requests.review' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teaching-support-change-requests/{id}/review',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@review',
        'controller' => 'Modules\\Schedule\\Application\\TeachingSupportChangeRequest\\TeachingSupportChangeRequestController@review',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'teaching-support-change-requests.review',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'change-request.review' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'change-requests/{id}/review',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\ReviewChangeRequest\\ReviewChangeRequestController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\ReviewChangeRequest\\ReviewChangeRequestController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'change-request.review',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'change-request.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'change-requests/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateChangeRequest\\CreateChangeRequestPageController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\CreateChangeRequest\\CreateChangeRequestPageController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'change-request.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'change-request.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'change-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateChangeRequest\\CreateChangeRequestController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\CreateChangeRequest\\CreateChangeRequestController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'change-request.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'change-request.holiday-reschedule.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'change-requests/holiday-reschedule',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateHolidayRescheduleRequest\\CreateHolidayRescheduleRequestController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\CreateHolidayRescheduleRequest\\CreateHolidayRescheduleRequestController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'change-request.holiday-reschedule.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'holiday-calendar.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'holiday-calendars',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\CreateHolidayCalendar\\CreateHolidayCalendarController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\CreateHolidayCalendar\\CreateHolidayCalendarController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'holiday-calendar.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'holiday-calendar.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'holiday-calendars/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\UpdateHolidayCalendar\\UpdateHolidayCalendarController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\UpdateHolidayCalendar\\UpdateHolidayCalendarController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'holiday-calendar.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'holiday-calendar.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'holiday-calendars/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\DeleteHolidayCalendar\\DeleteHolidayCalendarController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\DeleteHolidayCalendar\\DeleteHolidayCalendarController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'holiday-calendar.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester-events.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedules/{id}/semester-events',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@index',
        'controller' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester-events.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester-events.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'schedules/{id}/semester-events',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@store',
        'controller' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester-events.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester-events.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'schedules/{id}/semester-events/{eventId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@update',
        'controller' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester-events.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester-events.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'schedules/{id}/semester-events/{eventId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@destroy',
        'controller' => 'Modules\\Schedule\\Application\\ManageSemesterEvents\\ManageSemesterEventsController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester-events.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'semester/{semester?}/{year?}/{className?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\GetScheduleSemester\\GetScheduleSemesterController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\GetScheduleSemester\\GetScheduleSemesterController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.initialize.form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'monthly-schedules/initialize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\InitializeMonthlySchedule\\InitializeMonthlyScheduleController@showForm',
        'controller' => 'Modules\\Schedule\\Application\\InitializeMonthlySchedule\\InitializeMonthlyScheduleController@showForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.initialize.form',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'monthly-schedule.initialize' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'monthly-schedules/initialize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'Modules\\Schedule\\Application\\InitializeMonthlySchedule\\InitializeMonthlyScheduleController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\InitializeMonthlySchedule\\InitializeMonthlyScheduleController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'monthly-schedule.initialize',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::cul61H57ZngLijPA' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'schedule',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:324:"function () {
    $schedule = \\Modules\\Schedule\\Models\\MonthlySchedule::with(\'scheduleSlots\')
        ->where(\'class_name\', \'CD01\')
        ->where(\'month\', 3)
        ->where(\'year\', 2026)
        ->first();

    return \\view(\'schedule::schedule\', \\compact(\'schedule\')); // phai dung namespace de truy cap view cua module
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000ad10000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::cul61H57ZngLijPA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'schedule.semester.public' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'semester-schedule/{semester?}/{year?}/{className?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Modules\\Schedule\\Application\\GetScheduleSemester\\GetScheduleSemesterController@__invoke',
        'controller' => 'Modules\\Schedule\\Application\\GetScheduleSemester\\GetScheduleSemesterController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'schedule.semester.public',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teacher-accounts.activate.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teacher-accounts/activate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ActivateAccount\\ShowActivateAccountController@__invoke',
        'controller' => 'App\\Application\\Auth\\ActivateAccount\\ShowActivateAccountController',
        'as' => 'teacher-accounts.activate.show',
        'namespace' => NULL,
        'prefix' => '/teacher-accounts/activate',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teacher-accounts.activate.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teacher-accounts/activate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Application\\Auth\\ActivateAccount\\ActivateAccountController@__invoke',
        'controller' => 'App\\Application\\Auth\\ActivateAccount\\ActivateAccountController',
        'as' => 'teacher-accounts.activate.submit',
        'namespace' => NULL,
        'prefix' => '/teacher-accounts/activate',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-programs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-programs',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-programs.index',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-programs.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/training-programs',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-programs.store',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-programs.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-programs/{training_program}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-programs.show',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-programs.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/training-programs/{training_program}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-programs.update',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-programs.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/training-programs/{training_program}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-programs.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingPrograms\\ManageTrainingProgramsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-batches.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-batches',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-batches.index',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-batches.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/training-batches',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-batches.store',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-batches.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-batches/{training_batch}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-batches.show',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-batches.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/training-batches/{training_batch}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-batches.update',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-batches.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/training-batches/{training_batch}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-batches.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingBatches\\ManageTrainingBatchesController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.departments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/departments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.departments.index',
        'uses' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.departments.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/departments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.departments.store',
        'uses' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.departments.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/departments/{department}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.departments.show',
        'uses' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.departments.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/departments/{department}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.departments.update',
        'uses' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.departments.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/departments/{department}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.departments.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\Departments\\ManageDepartmentsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-classes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-classes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-classes.index',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-classes.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/training-classes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-classes.store',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-classes.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/training-classes/{training_class}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-classes.show',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-classes.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/training-classes/{training_class}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-classes.update',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.training-classes.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/training-classes/{training_class}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.training-classes.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\TrainingClasses\\ManageTrainingClassesController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.teachers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/teachers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.teachers.index',
        'uses' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.teachers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/teachers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.teachers.store',
        'uses' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.teachers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/teachers/{teacher}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.teachers.show',
        'uses' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.teachers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/teachers/{teacher}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.teachers.update',
        'uses' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.teachers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/teachers/{teacher}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.teachers.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\Teachers\\ManageTeachersController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.rooms.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/rooms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.rooms.index',
        'uses' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.rooms.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/rooms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.rooms.store',
        'uses' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.rooms.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/rooms/{room}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.rooms.show',
        'uses' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.rooms.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/rooms/{room}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.rooms.update',
        'uses' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.rooms.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/rooms/{room}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.rooms.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\Rooms\\ManageRoomsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subjects.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/subjects',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subjects.index',
        'uses' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subjects.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/subjects',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subjects.store',
        'uses' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subjects.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/subjects/{subject}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subjects.show',
        'uses' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subjects.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/subjects/{subject}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subjects.update',
        'uses' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subjects.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/subjects/{subject}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subjects.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\Subjects\\ManageSubjectsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subject-lessons.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/subject-lessons',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subject-lessons.index',
        'uses' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subject-lessons.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/subject-lessons',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subject-lessons.store',
        'uses' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subject-lessons.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/subject-lessons/{subject_lesson}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subject-lessons.show',
        'uses' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subject-lessons.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/subject-lessons/{subject_lesson}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subject-lessons.update',
        'uses' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.subject-lessons.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/subject-lessons/{subject_lesson}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.subject-lessons.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\SubjectLessons\\ManageSubjectLessonsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.import-template' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/students/import-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@downloadImportTemplate',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@downloadImportTemplate',
        'as' => 'management.students.import-template',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.import' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/students/import',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@import',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@import',
        'as' => 'management.students.import',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.students.index',
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@index',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@index',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'management/students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.students.store',
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@store',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@store',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'management/students/{student}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.students.show',
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@show',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@show',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'management/students/{student}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.students.update',
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@update',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@update',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'management.students.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'management/students/{student}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'management.access',
        ),
        'as' => 'management.students.destroy',
        'uses' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@destroy',
        'controller' => 'Modules\\Training\\Application\\Management\\Students\\ManageStudentsController@destroy',
        'namespace' => NULL,
        'prefix' => '/management',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'duty-log.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'duty-log',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherDailyLog\\GetTeacherDailyLogController@__invoke',
        'controller' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherDailyLog\\GetTeacherDailyLogController',
        'as' => 'duty-log.index',
        'namespace' => NULL,
        'prefix' => '/duty-log',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'duty-log.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'duty-log/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherDailyLog\\GetTeacherDailyLogEditController@__invoke',
        'controller' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherDailyLog\\GetTeacherDailyLogEditController',
        'as' => 'duty-log.edit',
        'namespace' => NULL,
        'prefix' => '/duty-log',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'duty-log.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'duty-log/submit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Training\\Application\\TeacherEvaluation\\SubmitDailyLog\\SubmitDailyLogController@__invoke',
        'controller' => 'Modules\\Training\\Application\\TeacherEvaluation\\SubmitDailyLog\\SubmitDailyLogController',
        'as' => 'duty-log.submit',
        'namespace' => NULL,
        'prefix' => '/duty-log',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teacher-slot-evaluations.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'teacher-slot-evaluations',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherSlotEvaluation\\GetTeacherSlotEvaluationController@__invoke',
        'controller' => 'Modules\\Training\\Application\\TeacherEvaluation\\GetTeacherSlotEvaluation\\GetTeacherSlotEvaluationController',
        'as' => 'teacher-slot-evaluations.index',
        'namespace' => NULL,
        'prefix' => '/teacher-slot-evaluations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'teacher-slot-evaluations.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'teacher-slot-evaluations/submit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'Modules\\Training\\Application\\TeacherEvaluation\\SubmitTeacherSlotEvaluation\\SubmitTeacherSlotEvaluationController@__invoke',
        'controller' => 'Modules\\Training\\Application\\TeacherEvaluation\\SubmitTeacherSlotEvaluation\\SubmitTeacherSlotEvaluationController',
        'as' => 'teacher-slot-evaluations.submit',
        'namespace' => NULL,
        'prefix' => '/teacher-slot-evaluations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:39:"E:\\Em trai\\qlhlCDHC\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"0000000000000b2c0000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local.upload' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:39:"E:\\Em trai\\qlhlCDHC\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:325:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ReceiveFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"0000000000000b330000000000000000";}}',
        'as' => 'storage.local.upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
