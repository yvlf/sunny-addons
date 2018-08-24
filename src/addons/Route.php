<?php
/**
 * +----------------------------------------------------------------------
 * | 插件执行默认控制器
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2018-08-23 12:10:30
 * | Author: Sunny (admin@sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace Sunny\addons;

use function print_r;
use think\facade\Config;
use think\exception\HttpException;
use think\facade\Hook;
use think\Loader;
use think\facade\Request;

class Route
{

    /**
     * 插件执行
     * @param null $addon
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public function execute($addon = null, $controller = null, $action = null)
    {

        $request = Request::instance();
        // 是否自动转换控制器和操作名
        $convert = Config::get('url_convert');

        // 获取插件名称，插件控制器名称，操作名称
        $filter = $convert ? 'strtolower' : 'trim';
        $addon = $addon ? trim(call_user_func($filter, $addon)) : '';
        $controller = $controller ? trim(call_user_func($filter, $controller)) : 'index';
        $action = $action ? trim(call_user_func($filter, $action)) : 'index';

        Hook::listen('addon_begin', $request);
        if (!empty($addon) && !empty($controller) && !empty($action)) {
            $info = get_addon_info($addon);
            if (!$info) {
                throw new HttpException(404, sprintf('addon %s not found', $addon));
            }
            if (!$info['state']) {
                throw new HttpException(500, sprintf('addon %s is disabled', $addon));
            }
            // 获取路由调度信息
            $dispatch = $request->dispatch()->getDispatch();
            if (isset($dispatch['var']) && $dispatch['var']) {
                $request->route($dispatch['var']);
            }

            // 设置当前请求的控制器、操作
            $request->setController($controller)->setAction($action);
            // 设置插件名称，由于ThinkPHP5.1使用了容器概念
            // 所有的request从门面获取出来都是单例的，在这里直接给属性赋值，别的地方也可以拿到
            $request->addon = $addon;

            // 监听addon_module_init
            Hook::listen('addon_module_init', $request);
            $class = get_addon_class($addon, 'controller', $controller);
            if (!$class) {
                throw new HttpException(404, sprintf('addon controller %s not found', Loader::parseName($controller, 1)));
            }
            $instance = new $class();

            $vars = [];
            if (is_callable([$instance, $action])) {
                // 执行操作方法
                $call = [$instance, $action];
            } elseif (is_callable([$instance, '_empty'])) {
                // 空操作
                $call = [$instance, '_empty'];
                $vars = [$action];
            } else {
                // 操作不存在
                throw new HttpException(404, sprintf('addon action %s not found', get_class($instance) . '->' . $action . '()'));
            }

            Hook::listen('addon_action_begin', $call);

            return call_user_func_array($call, $vars);
        } else {
            abort(500, lang('addon can not be empty'));
        }
    }

}
