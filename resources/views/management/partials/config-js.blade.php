            const resources = {
                trainingPrograms: {
                    label: 'Chương trình đào tạo',
                    endpoint: '{{ url('/management/training-programs') }}',
                    import: {
                        endpoint: '{{ url('/management/training-programs/import') }}',
                        templateUrl: '{{ route('management.training-programs.import-template') }}',
                        title: 'Nhap danh sach chuong trinh dao tao tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: code, name. Cot tuy chon: status (active/inactive/archived, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã CĐT'
                        },
                        {
                            key: 'name',
                            label: 'Tên chương trình'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    fields: [{
                            key: 'code',
                            label: 'Mã chương trình đào tạo',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên chương trình đào tạo',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                },
                                {
                                    value: 'archived',
                                    label: 'archived'
                                }
                            ]
                        }
                    ]
                },
                trainingBatches: {
                    label: 'Khóa học',
                    endpoint: '{{ url('/management/training-batches') }}',
                    import: {
                        endpoint: '{{ url('/management/training-batches/import') }}',
                        templateUrl: '{{ route('management.training-batches.import-template') }}',
                        title: 'Nhap danh sach khoa hoc tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: training_program_code, code, name. Cot tuy chon: status (active/inactive/archived, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã khóa'
                        },
                        {
                            key: 'name',
                            label: 'Tên khóa'
                        },
                        {
                            key: 'training_program.name',
                            label: 'Chương trình'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    fields: [{
                            key: 'training_program_id',
                            label: 'Chương trình đào tạo',
                            type: 'select',
                            lookup: 'trainingPrograms',
                            required: true
                        },
                        {
                            key: 'code',
                            label: 'Mã khóa',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên khóa',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                },
                                {
                                    value: 'archived',
                                    label: 'archived'
                                }
                            ]
                        }
                    ]
                },
                departments: {
                    label: 'Department',
                    endpoint: '{{ url('/management/departments') }}',
                    import: {
                        endpoint: '{{ url('/management/departments/import') }}',
                        templateUrl: '{{ route('management.departments.import-template') }}',
                        title: 'Nhap danh sach khoa/bo mon tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: code, name. Cot tuy chon: description, status (active/inactive, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Ma'
                        },
                        {
                            key: 'name',
                            label: 'Ten'
                        },
                        {
                            key: 'description',
                            label: 'Mo ta',
                            wrap: true
                        },
                        {
                            key: 'status',
                            label: 'Trang thai'
                        }
                    ],
                    fields: [{
                            key: 'code',
                            label: 'Ma department',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Ten department',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'description',
                            label: 'Mo ta',
                            type: 'textarea'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                }
                            ]
                        }
                    ]
                },
                trainingClasses: {
                    label: 'Lớp học',
                    endpoint: '{{ url('/management/training-classes') }}',
                    import: {
                        endpoint: '{{ url('/management/training-classes/import') }}',
                        templateUrl: '{{ route('management.training-classes.import-template') }}',
                        title: 'Nhap danh sach lop hoc tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: code, name, training_batch_code, total_students. Cot tuy chon: course_year, default_room_code, status (active/inactive/archived, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã lớp'
                        },
                        {
                            key: 'name',
                            label: 'Tên lớp'
                        },
                        {
                            key: 'course_year',
                            label: 'Năm học'
                        },
                        {
                            key: 'total_students',
                            label: 'Tổng quân số'
                        },
                        {
                            key: 'default_room.name',
                            label: 'Phòng mặc định'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    fields: [{
                            key: 'code',
                            label: 'Mã lớp',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên lớp',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'training_batch_id',
                            label: 'Khóa học',
                            type: 'select',
                            lookup: 'trainingBatches',
                            required: true
                        },
                        {
                            key: 'course_year',
                            label: 'Năm học',
                            type: 'number'
                        },
                        {
                            key: 'total_students',
                            label: 'Tổng quân số',
                            type: 'number',
                            required: true
                        },
                        {
                            key: 'default_room_id',
                            label: 'Phòng mặc định',
                            type: 'select',
                            lookup: 'rooms'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                },
                                {
                                    value: 'archived',
                                    label: 'archived'
                                }
                            ]
                        }
                    ]
                },
                teachers: {
                    label: 'Giáo viên',
                    endpoint: '{{ url('/management/teachers') }}',
                    import: {
                        endpoint: '{{ url('/management/teachers/import') }}',
                        templateUrl: '{{ route('management.teachers.import-template') }}',
                        title: 'Nhap danh sach giao vien tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 500 dong.',
                        hint: 'Cot bat buoc: teacher_code, name, email, department_code. Cot tuy chon: status (active/inactive, mac dinh active). Moi dong se tao 1 tai khoan giao vien va gui email moi kich hoat.',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'teacher_code',
                            label: 'Ma GV'
                        },
                        {
                            key: 'name',
                            label: 'Ten giao vien'
                        },
                        {
                            key: 'department.name',
                            label: 'Department'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai'
                        }
                    ],
                    fields: [{
                            key: 'teacher_code',
                            label: 'Ma giao vien',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Ten giao vien',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'email',
                            label: 'Email (dung de gui thu kich hoat tai khoan, chi nhap khi tao moi)',
                            type: 'email',
                            required: true
                        },
                        {
                            key: 'department_id',
                            label: 'Department',
                            type: 'select',
                            lookup: 'departments',
                            required: true
                        },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                }
                            ]
                        }
                    ]
                },
                rooms: {
                    label: 'Phong hoc',
                    endpoint: '{{ url('/management/rooms') }}',
                    import: {
                        endpoint: '{{ url('/management/rooms/import') }}',
                        templateUrl: '{{ route('management.rooms.import-template') }}',
                        title: 'Nhap danh sach phong hoc tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: code, name. Cot tuy chon: capacity, room_type, status (active/inactive/maintenance, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Ma phong'
                        },
                        {
                            key: 'name',
                            label: 'Ten phong'
                        },
                        {
                            key: 'capacity',
                            label: 'Suc chua'
                        },
                        {
                            key: 'room_type',
                            label: 'Loai phong'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai'
                        }
                    ],
                    fields: [{
                            key: 'code',
                            label: 'Ma phong',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Ten phong',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'capacity',
                            label: 'Suc chua',
                            type: 'number'
                        },
                        {
                            key: 'room_type',
                            label: 'Loai phong',
                            type: 'text'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                },
                                {
                                    value: 'maintenance',
                                    label: 'maintenance'
                                }
                            ]
                        }
                    ]
                },
                subjects: {
                    label: 'Mon hoc',
                    endpoint: '{{ url('/management/subjects') }}',
                    import: {
                        endpoint: '{{ url('/management/subjects/import') }}',
                        templateUrl: '{{ route('management.subjects.import-template') }}',
                        title: 'Nhap danh sach mon hoc tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: code, name, department_code. Cot tuy chon: total_periods, status (active/inactive, mac dinh active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Ma mon'
                        },
                        {
                            key: 'name',
                            label: 'Ten mon'
                        },
                        {
                            key: 'department.name',
                            label: 'Department'
                        },
                        {
                            key: 'total_periods',
                            label: 'Tong tiet'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai'
                        }
                    ],
                    fields: [{
                            key: 'department_id',
                            label: 'Department',
                            type: 'select',
                            required: true,
                            lookup: 'departments'
                        },
                        {
                            key: 'code',
                            label: 'Ma mon',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Ten mon',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'total_periods',
                            label: 'Tong tiet',
                            type: 'number'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'inactive',
                                    label: 'inactive'
                                }
                            ]
                        }
                    ]
                },
                subjectLessons: {
                    label: 'Bai hoc',
                    endpoint: '{{ url('/management/subject-lessons') }}',
                    import: {
                        endpoint: '{{ url('/management/subject-lessons/import') }}',
                        templateUrl: '{{ route('management.subject-lessons.import-template') }}',
                        title: 'Nhap danh sach bai hoc tu CSV',
                        description: 'Tai file CSV toi da 5 MB va 1.000 dong.',
                        hint: 'Cot bat buoc: subject_code, code (so thu tu bai hoc), name (tieu de). Cot tuy chon: expected_periods, note.',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'subject.code',
                            label: 'Ma mon'
                        },
                        {
                            key: 'lesson_no',
                            label: 'So bai'
                        },
                        {
                            key: 'title',
                            label: 'Tieu de',
                            wrap: true
                        },
                        {
                            key: 'expected_periods',
                            label: 'Tiet du kien'
                        }
                    ],
                    fields: [{
                            key: 'subject_id',
                            label: 'Mon hoc',
                            type: 'select',
                            lookup: 'subjects',
                            required: true
                        },
                        {
                            key: 'lesson_no',
                            label: 'So bai',
                            type: 'number',
                            required: true
                        },
                        {
                            key: 'title',
                            label: 'Tieu de',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'expected_periods',
                            label: 'Tiet du kien',
                            type: 'number'
                        },
                        {
                            key: 'note',
                            label: 'Ghi chu',
                            type: 'textarea'
                        }
                    ]
                },
                students: {
                    label: 'Hoc vien',
                    endpoint: '{{ url('/management/students') }}',
                    import: {
                        endpoint: '{{ url('/management/students/import') }}',
                        templateUrl: '{{ route('management.students.import-template') }}',
                        title: 'Nhap danh sach hoc vien tu CSV',
                        description: 'Chon lop, sau do tai file CSV toi da 5 MB va 5.000 dong.',
                        hint: 'Cot bat buoc: student_code, name. Cot tuy chon: date_of_birth, status.',
                        needsClass: true,
                    },
                    columns: [{
                            key: 'student_code',
                            label: 'Ma hoc vien'
                        },
                        {
                            key: 'name',
                            label: 'Ten hoc vien'
                        },
                        {
                            key: 'training_class.code',
                            label: 'Lop'
                        },
                        {
                            key: 'date_of_birth',
                            label: 'Ngay sinh'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai'
                        }
                    ],
                    fields: [{
                            key: 'class_id',
                            label: 'Lop hoc',
                            type: 'select',
                            lookup: 'trainingClasses',
                            required: true
                        },
                        {
                            key: 'student_code',
                            label: 'Ma hoc vien',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Ten hoc vien',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'date_of_birth',
                            label: 'Ngay sinh',
                            type: 'date'
                        },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [{
                                    value: 'active',
                                    label: 'active'
                                },
                                {
                                    value: 'suspended',
                                    label: 'suspended'
                                },
                                {
                                    value: 'graduated',
                                    label: 'graduated'
                                }
                            ]
                        }
                    ]
                }
            };

            const lookupLabels = {
                departments: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                trainingClasses: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                trainingPrograms: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                trainingBatches: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                rooms: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                subjects: (item) => [item.code, item.name].filter(Boolean).join(' - '),
            };

            const state = {
                currentResource: 'trainingPrograms',
                rows: [],
                page: 1,
                lastPage: 1,
                perPage: 10,
                total: 0,
                q: '',
                editingId: null,
                lookups: {
                    departments: [],
                    trainingClasses: [],
                    trainingPrograms: [],
                    trainingBatches: [],
                    rooms: [],
                    subjects: [],
                }
            };
