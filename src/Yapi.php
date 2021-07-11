<?php


namespace fuyelk\yapi;

class Yapi
{
    /**
     * Cookie文件
     */
    private static $COOKIE_FILE = __DIR__ . '/cookie.cookies';

    /**
     * 账号校验文件
     */
    private static $CHECK_FILE = __DIR__ . '/account.check';

    /**
     * 服务器HOST
     */
    private $host = '';

    /**
     * @var string
     */
    private $email = "";

    /**
     * @var string
     */
    private $password = "";

    /**
     * @var string
     */
    private $_error = "";

    /**
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    public function __construct($email, $password, $host)
    {
        $this->email = $email;
        $this->password = $password;
        $this->host = $host;

        $accountCheck = md5(serialize([$email, $password, $host]));

        if (is_file(self::$CHECK_FILE)) {
            // 存在旧的账号校验文件
            $oldCheck = file_get_contents(self::$CHECK_FILE);
            if ($oldCheck != $accountCheck) {
                // 更换了账号，则清空cookie
                $this->clearCookie();
            }
        } else {
            // 没有账号校验文件，则创建
            $this->clearCookie();
            file_put_contents(self::$CHECK_FILE, $accountCheck);
        }
    }

    /**
     * http请求
     * @param string $api http地址
     * @param string $method 请求方式
     * @param array $data 请求数据：
     * <pre>
     *  $data = [
     *      'image' => new \CURLFile($filePath),
     *      'access_token' => 'this-is-access-token'
     *       ...
     *  ]
     * </pre>
     * @param bool $checkResponseData 检查返回数据
     * @param bool $checkLogin 检查登录
     * @return array|bool|string
     * @throws \Exception
     */
    protected function request($api, $method = 'GET', $data = [], $checkResponseData = false, $checkLogin = true)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_ACCEPT_ENCODING => 'gzip,deflate',
            CURLOPT_URL => $this->host . $api,
            CURLOPT_CUSTOMREQUEST => strtoupper($method), // 请求方式
            CURLOPT_USERAGENT => "Mozilla / 5.0 (Windows NT 10.0; Win64; x64)",// 模拟常用浏览器的useragent
            CURLOPT_RETURNTRANSFER => true,   // 获取的信息以文件流的形式返回，而不是直接输出
            CURLOPT_SSL_VERIFYPEER => false,  // https请求不验证证书
            CURLOPT_SSL_VERIFYHOST => false,  // https请求不验证hosts
            CURLOPT_MAXREDIRS => 10,          // 最深允许重定向级数
            CURLOPT_CONNECTTIMEOUT => 10,// 最长等待连接成功时间
            CURLOPT_TIMEOUT => 50,      // 最长等待响应完成时间
            CURLOPT_COOKIEJAR => self::$COOKIE_FILE, // 设置cookie存储文件
            CURLOPT_COOKIEFILE => self::$COOKIE_FILE // 设置cookie上传文件
        ]);

        $addHeader = [];
        // 发送请求数据
        if ($data) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            array_push($addHeader, 'Content-type:application/json');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $addHeader); // 设置请求头
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \Exception($err);

        if ($checkLogin) {
            // 接口返回未登录标识，未登录则尝试登录一次
            $data = json_decode($response, true);

            if (!empty($response) &&
                $data &&
                !empty($data['errcode']) &&
                '40011' == strval($data['errcode'])
            ) {
                $login_res = $this->login();
                if (!$login_res) {
                    throw new \Exception($this->_error);
                }
                // 重发一次当前请求，不再检查登录
                return $this->request($api, $method, $data, $checkResponseData, false);
            }
        }

        if ($checkResponseData) {
            $response = json_decode($response, true);
            if (!empty($response['errcode']) && !empty($response['errmsg'])) {
                throw new \Exception($response['errmsg']);
            }

            if (!isset($response['data'])) {
                throw new \Exception('数据有误');
            }
            return $response['data'];
        }

        return $response;
    }

    /**
     * 登录
     * @return bool
     * @throws \Exception
     */
    private function login()
    {
        $api = '/api/user/login';
        $data = [
            'email' => $this->email,
            'password' => $this->password
        ];

        try {
            self::request($api, 'POST', $data, true, false);
        } catch (\Exception $e) {
            $this->_error = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 清除Cookie
     * @return bool
     * @author fuyelk <fuyelk@fuyelk.com>
     */
    public function clearCookie()
    {
        if (is_file(self::$COOKIE_FILE)) {
            @unlink(self::$COOKIE_FILE);
        }
        return true;
    }

    /**
     * 导出文档
     * @param $project_id
     * @param string $type 导出文档类别，支持html/markdown/json/swagger
     * @param string $filePath 到处文件路径
     * @return bool
     */
    public function export($projectId, $type, $filePath)
    {
        switch ($type) {
            case 'html' :
                $api = "/api/plugin/export?type=html&pid={$projectId}&status=all&isWiki=false";
                break;
            case 'json':
                $api = "/api/plugin/export?type=json&pid={$projectId}&status=all&isWiki=false";
                break;
            case 'markdown':
                $api = "/api/plugin/export?type=markdown&pid={$projectId}&status=all&isWiki=false";
                break;
            case 'swagger':
                $api = "/api/plugin/exportSwagger?type=OpenAPIV2&pid={$projectId}&status=all&isWiki=false";
                break;
            default :
                $this->_error = '导出文件类型有误';
                return false;
        }

        try {
            $content = $this->request($api, 'get');
        } catch (\Exception $e) {
            $this->_error = $e->getMessage();
            return false;
        }
        file_put_contents($filePath, $content);
        return true;
    }
}