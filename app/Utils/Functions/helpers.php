<?php

use App\Constant\JsonResponseCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;

if (!function_exists('paginate_to_apidata')) {
    /**
     * paginate对象装换Api数据结构辅助函数
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginate
     *
     * @return array
     */
    function paginate_to_apidata(LengthAwarePaginator $paginate)
    {
        return [
            'paginate' => [
                'items'        => $paginate->items(),
                'total'        => $paginate->total(),
                'last_page'    => $paginate->lastPage(),
                'current_page' => $paginate->currentPage(),
                'page_size'    => $paginate->perPage()
            ]
        ];
    }
}

if (!function_exists('build_successful_body')) {
    /**
     * json 成功响应体
     *
     * @param array  $response
     * @param string $message
     *
     * @return \SuperHappysir\Support\Utils\Response\JsonResponseBodyInterface
     */
    function build_successful_body($response = [], $message = '')
    {
        // 分页对象
        if ($response instanceof LengthAwarePaginator) {
            $response = paginate_to_apidata($response);
        }
    
        // 实现Arrayable的对象
        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }
    
        !$message && $message = JsonResponseCode::getName(JsonResponseCode::SUCCESS) ?? '';
        
        return json_response_body(JsonResponseCode::SUCCESS, $message, $response);
    }
}

if (!function_exists('build_wrong_body')) {
    /**
     * json 失败响应体
     *
     * @param string $code
     * @param string $message
     *
     * @return \SuperHappysir\Support\Utils\Response\JsonResponseBodyInterface
     */
    function build_wrong_body(string $code, string $message)
    {
        return json_response_body($code, $message, []);
    }
}

if (!function_exists('absolute_resources_path')) {
    /**
     * 获取资源绝对路径
     *
     * @param string $relativePath
     *
     * @return string
     */
    function resources_path(string $relativePath)
    {
        return sprintf('%s/%s', config('filesystems.disks.public.upyun.domain'), $relativePath);
    }
}
