            const resources = {
                trainingPrograms: {
                    label: 'Chương trình đào tạo',
                    endpoint: '{{ url('/management/training-programs') }}',
                    import: {
                        endpoint: '{{ url('/management/training-programs/import') }}',
                        templateUrl: '{{ route('management.training-programs.import-template') }}',
                        title: 'Nhập danh sách chương trình đào tạo từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: code, name. Cột tùy chọn: status (active/inactive/archived, mặc định active).',
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
                    filters: [{
                        key: 'status',
                        label: 'Trạng thái',
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
                    }],
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
                        },
                        {
                            key: 'subject_ids',
                            label: 'Danh sách môn học áp dụng',
                            type: 'multiselect',
                            lookup: 'subjects',
                            relationKey: 'subjects'
                        }
                    ]
                },
                trainingBatches: {
                    label: 'Khóa học',
                    endpoint: '{{ url('/management/training-batches') }}',
                    import: {
                        endpoint: '{{ url('/management/training-batches/import') }}',
                        templateUrl: '{{ route('management.training-batches.import-template') }}',
                        title: 'Nhập danh sách khóa học từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: training_program_code, code, name. Cột tùy chọn: status (active/inactive/archived, mặc định active).',
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
                    filters: [{
                            key: 'training_program_id',
                            label: 'Chương trình đào tạo',
                            lookup: 'trainingPrograms'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
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
                    label: 'Khoa',
                    endpoint: '{{ url('/management/departments') }}',
                    import: {
                        endpoint: '{{ url('/management/departments/import') }}',
                        templateUrl: '{{ route('management.departments.import-template') }}',
                        title: 'Nhập danh sách khoa/bộ môn từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: code, name. Cột tùy chọn: description, status (active/inactive, mặc định active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã'
                        },
                        {
                            key: 'name',
                            label: 'Tên'
                        },
                        {
                            key: 'description',
                            label: 'Mô tả',
                            wrap: true
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    filters: [{
                        key: 'status',
                        label: 'Trạng thái',
                        options: [{
                                value: 'active',
                                label: 'active'
                            },
                            {
                                value: 'inactive',
                                label: 'inactive'
                            }
                        ]
                    }],
                    fields: [{
                            key: 'code',
                            label: 'Mã department',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên department',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'description',
                            label: 'Mô tả',
                            type: 'textarea'
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
                        title: 'Nhập danh sách lớp học từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: code, name, training_batch_code, total_students. Cột tùy chọn: course_year, default_room_code, status (active/inactive/archived, mặc định active).',
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
                    filters: [{
                            key: 'training_batch_id',
                            label: 'Khóa học',
                            lookup: 'trainingBatches'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
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
                        title: 'Nhập danh sách giáo viên từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 500 dòng.',
                        hint: 'Cột bắt buộc: teacher_code, name, email, department_code. Cột tùy chọn: status (active/inactive, mặc định active). Mỗi dòng sẽ tạo 1 tài khoản giáo viên và gửi email mời kích hoạt.',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'teacher_code',
                            label: 'Mã GV'
                        },
                        {
                            key: 'name',
                            label: 'Tên giáo viên'
                        },
                        {
                            key: 'department.name',
                            label: 'Department'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    filters: [{
                            key: 'department_id',
                            label: 'Department',
                            lookup: 'departments'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
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
                    ],
                    fields: [{
                            key: 'teacher_code',
                            label: 'Mã giáo viên',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên giáo viên',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'email',
                            label: 'Email (dùng để gửi thư kích hoạt tài khoản, chỉ nhập khi tạo mới)',
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
                                }
                            ]
                        }
                    ]
                },
                rooms: {
                    label: 'Phòng học',
                    endpoint: '{{ url('/management/rooms') }}',
                    import: {
                        endpoint: '{{ url('/management/rooms/import') }}',
                        templateUrl: '{{ route('management.rooms.import-template') }}',
                        title: 'Nhập danh sách phòng học từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: code, name. Cột tùy chọn: capacity, room_type, status (active/inactive/maintenance, mặc định active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã phòng'
                        },
                        {
                            key: 'name',
                            label: 'Tên phòng'
                        },
                        {
                            key: 'capacity',
                            label: 'Sức chứa'
                        },
                        {
                            key: 'room_type',
                            label: 'Loại phòng'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    filters: [{
                        key: 'status',
                        label: 'Trạng thái',
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
                    }],
                    fields: [{
                            key: 'code',
                            label: 'Mã phòng',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên phòng',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'capacity',
                            label: 'Sức chứa',
                            type: 'number'
                        },
                        {
                            key: 'room_type',
                            label: 'Loại phòng',
                            type: 'text'
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
                                    value: 'maintenance',
                                    label: 'maintenance'
                                }
                            ]
                        }
                    ]
                },
                subjects: {
                    label: 'Môn học',
                    endpoint: '{{ url('/management/subjects') }}',
                    import: {
                        endpoint: '{{ url('/management/subjects/import') }}',
                        templateUrl: '{{ route('management.subjects.import-template') }}',
                        title: 'Nhập danh sách môn học từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: code, name, department_code. Cột tùy chọn: total_periods, status (active/inactive, mặc định active).',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'code',
                            label: 'Mã môn'
                        },
                        {
                            key: 'name',
                            label: 'Tên môn'
                        },
                        {
                            key: 'department.name',
                            label: 'Department'
                        },
                        {
                            key: 'total_periods',
                            label: 'Tổng tiết'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    filters: [{
                            key: 'department_id',
                            label: 'Department',
                            lookup: 'departments'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
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
                            label: 'Mã môn',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên môn',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'total_periods',
                            label: 'Tổng tiết',
                            type: 'number'
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
                                }
                            ]
                        }
                    ]
                },
                subjectLessons: {
                    label: 'Bài học',
                    endpoint: '{{ url('/management/subject-lessons') }}',
                    import: {
                        endpoint: '{{ url('/management/subject-lessons/import') }}',
                        templateUrl: '{{ route('management.subject-lessons.import-template') }}',
                        title: 'Nhập danh sách bài học từ CSV',
                        description: 'Tải file CSV tối đa 5 MB và 1.000 dòng.',
                        hint: 'Cột bắt buộc: subject_code, code (số thứ tự bài học), name (tiêu đề). Cột tùy chọn: expected_periods, note.',
                        needsClass: false,
                    },
                    columns: [{
                            key: 'subject.code',
                            label: 'Mã môn'
                        },
                        {
                            key: 'lesson_no',
                            label: 'Số bài'
                        },
                        {
                            key: 'title',
                            label: 'Tiêu đề',
                            wrap: true
                        },
                        {
                            key: 'is_regular_test',
                            label: 'Kiểm tra thường xuyên'
                        },
                        {
                            key: 'expected_periods',
                            label: 'Tiết dự kiến'
                        }
                    ],
                    filters: [{
                            key: 'subject_id',
                            label: 'Môn học',
                            lookup: 'subjects'
                        },
                        {
                            key: 'is_regular_test',
                            label: 'Kiểm tra thường xuyên',
                            options: [{
                                    value: '1',
                                    label: 'Có'
                                },
                                {
                                    value: '0',
                                    label: 'Không'
                                }
                            ]
                        }
                    ],
                    fields: [{
                            key: 'subject_id',
                            label: 'Môn học',
                            type: 'select',
                            lookup: 'subjects',
                            required: true
                        },
                        {
                            key: 'lesson_no',
                            label: 'Số bài',
                            type: 'number',
                            required: true
                        },
                        {
                            key: 'title',
                            label: 'Tiêu đề',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'is_regular_test',
                            label: 'Là bài kiểm tra thường xuyên',
                            type: 'checkbox'
                        },
                        {
                            key: 'expected_periods',
                            label: 'Tiết dự kiến',
                            type: 'number'
                        },
                        {
                            key: 'note',
                            label: 'Ghi chú',
                            type: 'textarea'
                        }
                    ]
                },
                students: {
                    label: 'Học viên',
                    endpoint: '{{ url('/management/students') }}',
                    import: {
                        endpoint: '{{ url('/management/students/import') }}',
                        templateUrl: '{{ route('management.students.import-template') }}',
                        title: 'Nhập danh sách học viên từ CSV',
                        description: 'Chọn lớp, sau đó tải file CSV tối đa 5 MB và 5.000 dòng.',
                        hint: 'Cột bắt buộc: student_code, name. Cột tùy chọn: date_of_birth, status.',
                        needsClass: true,
                    },
                    columns: [{
                            key: 'student_code',
                            label: 'Mã học viên'
                        },
                        {
                            key: 'name',
                            label: 'Tên học viên'
                        },
                        {
                            key: 'training_class.code',
                            label: 'Lớp'
                        },
                        {
                            key: 'date_of_birth',
                            label: 'Ngày sinh',
                            type: 'date'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái'
                        }
                    ],
                    filters: [{
                            key: 'class_id',
                            label: 'Lớp học',
                            lookup: 'trainingClasses'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
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
                    ],
                    fields: [{
                            key: 'class_id',
                            label: 'Lớp học',
                            type: 'select',
                            lookup: 'trainingClasses',
                            required: true
                        },
                        {
                            key: 'student_code',
                            label: 'Mã học viên',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'name',
                            label: 'Tên học viên',
                            type: 'text',
                            required: true
                        },
                        {
                            key: 'date_of_birth',
                            label: 'Ngày sinh',
                            type: 'date'
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
                filters: {},
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
