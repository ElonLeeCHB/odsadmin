<?php

namespace App\Domains\Admin\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ReceivingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 這裡可以加入權限邏輯
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|integer',
            'products'       => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.quantity'   => 'required|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => '廠商代號不能是空值',
            'products.required'       => '請至少選擇一項商品',
            'products.*.product_id.required' => '商品 ID 不能為空',
            'products.*.quantity.required'   => '數量不能為空',
            'products.*.quantity.min'        => '數量必須至少為 1',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = ['errors' => []];

        foreach ($validator->errors()->messages() as $field => $messages) {
            $response['errors'][$field] = $messages[0]; // 只取第一條錯誤訊息
        }

        throw new \Illuminate\Validation\ValidationException($validator, response()->json($response, 422));
    }

    /**
     * 第二階段的資料處理：補值處理
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();

        // 補預設值
        if (empty($data['moq'])) {
            $data['moq'] = 10;
        }

        foreach ($data['products'] as &$product) {
            if (empty($product['quantity'])) {
                $product['quantity'] = 1;
            }
        }

        return $data;
    }
}
