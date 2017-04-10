# Laravel5.2多表认证登录
## 简介
`Laravel`中实现用户认证非常简单。实际上，几乎所有东西都已经为你配置好了。配置文件位于`config/auth.php`，其中包含了用于调整认证服务行为的、文档友好的选项配置。

在底层代码中，`Laravel` 的认证组件由 `guards` 和 `providers`组成，`Guard` 定义了用户在每个请求中如何实现认证，例如，`Laravel` 通过 `session guard`来维护 `Session` 存储的状态、`Cookie` 以及 `token guard`，`token guard` 是认证用户发送请求时带的API token。
> 如果将Laravel比作一辆跑车，config/auth.php配置文件相当于跑车的引擎。引擎是由 guards 和 providers 共同组成，两者缺一不可，缺少任意一个引擎将会瘫痪，如果你把引擎搞瘫痪了，那你根本就不是老司机啦~
  跑车的引擎是可以更换的，在 Laravel 中同样也可以自定义 guards 和 providers ，Laravel 这辆超级跑车能让你随心所欲的换零件(组件式开发和面向接口编程的好处)，至于你换F1发动机还是换涡轮增压发动机都行，只能能跑就行了~
  OK，Laravel的用户认证就简单介绍到这里，总结一下。要实现多用户表的登录，只要给自己的 Laravel 跑车多加一个引擎，即让两个引擎同时工作。而一个引擎是由 guards 和 providers 组成，所以我们要自定义这两个配置。如果你是神豪，给跑车添加 n 个引擎理论上也是OK的！

# 添加默认引擎

> Laravel示例版本为5.2.*，.env 文件已经配置完毕

首先我们使用Laravel 5.2提供的开箱即用的认证：
```
    // 该Artisan命令会生成用户认证所需的路由、视图以及HomeController：
    $ php artisan make:auth
```

认证的路由也一并生成好了，查看路由文件routes.php，会发现该文件已经被更新:

```
    // 其中Route::auth()定义了登录注册及找回密码路由，/home 为需要用户认证的路由。
    Route::auth();
    Route::get('/home', 'HomeController@index');

```

# 开车

接下来我们先实现前台用户登录，也就是Laravel自带的User用户表登录。通过生成的默认登录认证，已经写好了所有代码，剩下要做的就是使用迁移命令创建用户认证相关表：

```
    $ php artisan migrate
```
执行命令后会生成 `users` 表和 `password_resets` 表，分别为用户表和密码重置表。然后我们就可以在浏览器中输入`http://localhost:8000/register`来注册新用户：
> 1.进入注册界面后手动注册一个前台用户就行
> 2.登录、找回密码功能我就不测试了~

# 新增引擎
通过上面的操作，我们为Laravel跑车添加默认了引擎。恭喜你，已经可以开车了~接下来我们要给Laravel跑车再加一个引擎。
首先要看看默认的引擎(用户认证)配置文件auth.php，配置如下：

```php
    <?php
    
    return [
    
        'defaults' => [
            'guard' => 'web',
            'passwords' => 'users',
        ],
    
        'guards' => [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'api' => [
                'driver' => 'token',
                'provider' => 'users',
            ],
        ],
    
        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => App\User::class,
            ],
        ],
    
        'passwords' => [
            'users' => [
                'provider' => 'users',
                'email' => 'auth.emails.password',
                'table' => 'password_resets',
               'expire' => 60,
            ],
        ],
    ];
```
引擎(认证)是由 `guard` 和 `provider` 两部分构成的（[参考用户认证文档](https://laravel.com/docs/5.2/authentication)），defaults 配置是选择哪一个 guard 引擎(认证)系统，所以我们在这两个配置项中分别添加一个 admin 和 admins 选项。

```php
    <?php
    
    return [
    
        'defaults' => [
            'guard' => 'web',
            'passwords' => 'users',
        ],
    
        'guards' => [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'admin' => [
                'driver' => 'session',
                'provider' => 'admins',
            ],
    
            'api' => [
                'driver' => 'token',
                'provider' => 'users',
            ],
        ],
    
    
        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => App\User::class,
            ],
            'admins' => [
                'driver' => 'eloquent',
                'model' => \App\Model\Admin::class,
            ],
        ],
    
        'passwords' => [
            'users' => [
                'provider' => 'users',
                'email' => 'auth.emails.password',
                'table' => 'password_resets',
                'expire' => 60,
            ],
        ],
    
    ];

```

## 创建后台用户Model

```
    $ php artisan make:model Model/Admin -m
```
带上`-m` 选项会生成对应迁移文件 `*_create_admins_table`，我们定义该数据表字段和users一样，你也可以自定义：

```php
    <?php
    
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;
    
    class CreateAdminsTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('admins', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    
        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::drop('admins');
        }
    }

```

然后修改`Admin`模型类如下

```php

    <?php
    
    namespace App\Model;
    
    use Illuminate\Foundation\Auth\User as Authenticatable;
    
    class Admin extends Authenticatable
    {
        protected $fillable = [
            'name', 'email', 'password',
        ];
    
        protected $hidden = [
            'password', 'remember_token',
        ];
    }

```

## 后台用户认证路由及控制器
在 `routes.php` 中添加如下代码：

```php
    Route::get('admin/login', 'Admin\LoginController@getLogin');
    Route::post('admin/login', 'Admin\LoginController@postLogin');
    Route::get('admin/register', 'Admin\LoginController@getRegister');
    Route::post('admin/register', 'Admin\LoginController@postRegister');
    Route::post('admin/logout', 'Admin\LoginController@logout');
    Route::get('admin', 'Admin\IndexController@index');
```

使用Artisan命令创建控制器：
```
    $ php artisan make:controller Admin/LgoinController 
    $ php artisan make:controller Admin/IndexController
```

编辑`Admin/LoginController.php`代码如下：

```php
    <?php
    
    namespace App\Http\Controllers\Admin;
    
    use App\Model\Admin;
    use Validator;
    use App\Http\Controllers\Controller;
    use Illuminate\Foundation\Auth\ThrottlesLogins;
    use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
    
    class LoginController extends Controller
    {
    
        use AuthenticatesAndRegistersUsers, ThrottlesLogins;
    
        protected $redirectTo = '/admin';
        protected $guard = 'admin';
        protected $loginView = 'multi-auth.login';
        protected $registerView = 'multi-auth.register';
    
        public function __construct()
        {
            $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
        }
    
        protected function validator(array $data)
        {
            return Validator::make($data, [
                'name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:admins',
                'password' => 'required|min:6|confirmed',
            ]);
        }
    
        protected function create(array $data)
        {
            return Admin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);
        }
    }

```

编辑Admin/IndexController.php代码如下：

```php
    <?php
    
    namespace App\Http\Controllers\Admin;
    
    use Illuminate\Http\Request;
    
    use App\Http\Requests;
    use App\Http\Controllers\Controller;
    
    class IndexController extends Controller
    {
        public function __construct()
        {
            $this->middleware('auth:admin');
        }
    
        public function index()
        {
            return auth('admin')->user()->name;
        }
    }

```

## 视图文件创建及修改
最后我们要创建后台用户认证对应视图文件，这里我们简单复制默认用户视图模板并稍作修改即可，复制 `resources\views\auth` 目录重名了为 `multi-auth` 。

修改`resources/views/multi-auth`目录下登录及注册表单提交地址：

```
/login -> /admin/login
/register -> /admin/register

```

> 在浏览器中访问`http://localhost:8000/admin/register`，同样显示注册页面：
> 注册成功后，页面跳转到`http://localhost:8000/admin`，说明注册成功！

OK，至此我们已经完成前后台用户同时登录认证功能。双引擎已经加上，老司机准备开车吧~
