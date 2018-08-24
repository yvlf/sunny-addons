# 基于TP5.1实现的插件管理类

本插件基于fastadmin的插件修改而来，兼容ThinkPHP5.1

原项目地址：[fastadmin-addons](https://github.com/karsonzhang/fastadmin-addons)

### 安装方式

``
composer require sunnyos/yee-addons
``

#### 配置

在config文件夹下新增文件 addons.php ，配置内容

```php
<?php
return [
    'autoload' => false,
    // 定义钩子，键名为钩子名称，值为钩子插件名
    'hooks' =>[
        'sunnyHook'=>'sunny'
    ],
    'route' =>
        [
            '/sunny$'=>'sunny/index/index',
            '/sunny/test$'=>'sunny/index/test'
        ],
];

```

## 创建插件
> 创建的插件可以在view视图中使用，也可以在php业务中使用
 
安装完成后访问系统时会在项目根目录生成名为`addons`的目录，在该目录中创建需要的插件。

下面写一个例子：

### 创建sunny插件
> 在addons目录中创建sunny目录

### 创建钩子实现类
> 在test目录中创建Sunny.php类文件。注意：类文件首字母需大写

```php
<?php
namespace addons\sunny;
use Sunny\Addons;
use function time;
class Sunny extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 实现的sunnyHook钩子方法
     * @return mixed
     */
    public function sunnyHook($param)
    {
        echo time();
    }
}

```

### 创建插件配置文件
> 在sunny目录中创建config.php类文件，插件配置文件可以省略。

```php
<?php
return [
    [
        'name' => 'rewrite',
        'title' => '伪静态',
        'type' => 'array',
        'content' => [],
        'value' =>
            [
                'index/index' => '/sunny',
                'index/build' => '/sunny/build$',
            ],
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
];

```

### 创建钩子模板文件
> 在sunny目录中创建view/index/index.html模板文件，钩子在使用fetch方法时对应的模板文件。

```
<h1>hello tpl</h1>

如果插件中需要有链接或提交数据的业务，可以在插件中创建controller业务文件，
要访问插件中的controller时使用addon_url生成url链接。
如下：
<a href="{:addon_url('sunny://Index/index')}">link test</a>
格式为：
sunny为插件名，Index为controller中的类名，index为controller中的方法
```

### 创建插件的controller文件
> 在test目录中创建controller目录，在controller目录中创建Index.php文件
> controller类的用法与tp5中的controller一致

```php
namespace addons\sunny\controller;
use Sunny\addons\Controller;
class Index extends Controller
{
    public function index(){
        echo time();
    }
}
```
> 如果需要使用view模板则需要继承`\think\addons\Controller`类
> 模板文件所在位置为插件目录的view中，规则与模块中的view规则一致

```php
<?php
namespace addons\sunny\controller;
use Sunny\addons\Controller;
class Index extends Controller
{
    public function index(){
        return $this->fetch();
    }
}
```

## 使用钩子
> 创建好插件后就可以在正常业务中使用该插件中的钩子了
> 使用钩子的时候第二个参数可以省略

### 模板中使用钩子

```
<div>{:hook('sunnyHook', ['id'=>1])}</div>
```

### php业务中使用
> 只要是thinkphp5正常流程中的任意位置均可以使用

```
hook('sunnyHook', ['id'=>1])
```

## 插件目录结构
### 最终生成的目录结构为

```
tp5
 - addons
 -- sunny
 --- controller
 ---- Index.php
 --- view
 ---- index
 ----- index.html
 --- config.php
 --- info.html
 --- Sunny.php
 - application
 - thinkphp
 - extend
 - vendor
 - public
```

访问路由在 config/addons.php 里面的route 参考配置，路由规则详情查看ThinkPHP官网5.1的手册