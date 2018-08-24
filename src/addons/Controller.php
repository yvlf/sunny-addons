<?php
/**
 * +----------------------------------------------------------------------
 * | 插件基类控制器
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2018-08-23 12:10:00
 * | Author: Sunny (admin@sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace Sunny\addons;

use function print_r;
use think\facade\App;
use think\facade\Config;
use think\facade\Lang;
use think\Loader;
use think\facade\Request;

class Controller extends \think\Controller
{

    // 当前插件操作
    protected $addon = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;

    /**
     * 布局模板
     * @var string
     */
    protected $layout = null;

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
        $request = Request::instance();

        // 生成request对象
        $this->request = $request;

        //移除HTML标签
        $this->request->filter('strip_tags');

        // 是否自动转换控制器和操作名
        $convert = Config::get('url_convert');

        $filter = $convert ? 'strtolower' : 'trim';
        // 处理路由参数
        $param = $this->request->param();
        $dispatch = $this->request->dispatch()->getDispatch();
        $var = isset($dispatch['var']) ? $dispatch['var'] : [];
        $var = array_merge($param, $var);
        if (isset($dispatch[0]) && substr($dispatch[0], 0, 5) == "\\addons") {
            $arr = explode("\\", $dispatch[0]);
            $addon = strtolower($arr[2]);
            $controller = strtolower(end($arr));
            $action = $dispatch[1];
        } else {
            $addon = $this->request->addon;
            $controller = $this->request->controller();
            $action = $this->request->action();
        }
        $this->addon = $addon ? call_user_func($filter, $addon) : '';
        $this->controller = $controller ? call_user_func($filter, $controller) : 'index';
        $this->action = $action ? call_user_func($filter, $action) : 'index';


        // 重置配置模版路径
        Config::set('template.view_path', ADDON_PATH . $this->addon . DS . 'view' . DS);

        // 父类的调用必须放在设置模板路径之后
        parent::__construct(App::getInstance());
    }

    protected function initialize()
    {
        // 渲染配置到视图中
        $config = get_addon_config($this->addon);
        $this->view->assign("config", $config);

        // 加载系统语言包
        Lang::load([
            ADDON_PATH . $this->addon . DS . 'lang' . DS . $this->request->langset() . EXT,
        ]);

        // 设置替换字符串
        $cdnurl = Config::get('site.cdnurl');
        $this->view->assign('__ADDON__', $cdnurl . "/assets/addons/" . $this->addon);
    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [],$config = [])
    {
        $controller = Loader::parseName($this->controller);
        if ('think' == strtolower(Config::get('template.type')) && $controller && 0 !== strpos($template, '/')) {
            $depr = Config::get('template.view_depr');
            $template = str_replace(['/', ':'], $depr, $template);
            if ('' == $template) {
                // 如果模板文件名为空 按照默认规则定位
                $template = str_replace('.', DS, $controller) . $depr . $this->action;
            } elseif (false === strpos($template, $depr)) {
                $template = str_replace('.', DS, $controller) . $depr . $template;
            }
        }
        return parent::fetch($template, $vars, $config);
    }

}
