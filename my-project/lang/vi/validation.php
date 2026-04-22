<?php

return [
    'accepted' => ':attribute phải được chấp nhận.',
    'active_url' => ':attribute không phải là một URL hợp lệ.',
    'after' => ':attribute phải là một ngày sau :date.',
    'alpha' => ':attribute chỉ được chứa chữ cái.',
    'alpha_dash' => ':attribute chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
    'alpha_num' => ':attribute chỉ được chứa chữ cái và số.',
    'array' => ':attribute phải là một mảng.',
    'before' => ':attribute phải là một ngày trước :date.',
    'between' => [
        'numeric' => ':attribute phải nằm giữa :min và :max.',
        'file' => ':attribute phải có kích thước từ :min đến :max KB.',
        'string' => ':attribute phải có độ dài từ :min đến :max ký tự.',
        'array' => ':attribute phải có từ :min đến :max phần tử.',
    ],
    'boolean' => ':attribute phải là true hoặc false.',
    'confirmed' => ':attribute xác nhận không khớp.',
    'date' => ':attribute không phải là ngày hợp lệ.',
    'date_format' => ':attribute không đúng định dạng :format.',
    'different' => ':attribute và :other phải khác nhau.',
    'digits' => ':attribute phải gồm :digits chữ số.',
    'email' => ':attribute phải là địa chỉ email hợp lệ.',
    'exists' => ':attribute không hợp lệ.',
    'image' => ':attribute phải là hình ảnh.',
    'in' => ':attribute không hợp lệ.',
    'integer' => ':attribute phải là số nguyên.',
    'max' => [
        'numeric' => ':attribute không được lớn hơn :max.',
        'file' => ':attribute không được lớn hơn :max KB.',
        'string' => ':attribute không được dài hơn :max ký tự.',
        'array' => ':attribute không được có nhiều hơn :max phần tử.',
    ],
    'min' => [
        'numeric' => ':attribute phải tối thiểu là :min.',
        'file' => ':attribute phải tối thiểu :min KB.',
        'string' => ':attribute phải có ít nhất :min ký tự.',
        'array' => ':attribute phải có ít nhất :min phần tử.',
    ],
    'not_in' => ':attribute không hợp lệ.',
    'numeric' => ':attribute phải là số.',
    'required' => ':attribute là bắt buộc.',
    'same' => ':attribute và :other phải giống nhau.',
    'size' => [
        'numeric' => ':attribute phải là :size.',
        'file' => ':attribute phải là :size KB.',
        'string' => ':attribute phải dài :size ký tự.',
        'array' => ':attribute phải chứa :size phần tử.',
    ],
    'string' => ':attribute phải là chuỗi.',
    'unique' => ':attribute đã tồn tại.',
    'url' => ':attribute không đúng định dạng URL.',

    'attributes' => [
    'name' => 'tên sản phẩm',
    'price' => 'giá',
    'category_id' => 'danh mục',
    'brand_id' => 'thương hiệu',
    'img' => 'ảnh sản phẩm',
    'detailed_description' => 'mô tả chi tiết'
    ],
    
];
