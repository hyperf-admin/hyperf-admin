在脚手架一结中已经介绍, `Controller`中的`module_class`属性, 告诉了我们要操作的是 `mysql`的那张表, 那么如果当我们要管理的数据存储方式是`elasticsearch`, `mongo`, 甚至是三方服务的`api`时 , 该怎么办呢?

此时, 我们抽象出了一个实体`entity`的概念, 源码见[这里](https://github.com/hyperf-admin/hyperf-admin/tree/master/src/base-utils/src/Scaffold/Entity), 实体接口如下

```php
interface EntityInterface
{
    public function create(array $data);

    public function set($id, array $data);

    public function get($id);

    public function delete($id);

    public function count($where);

    public function list($where, $attr = [], $page = 1, $size = 20);

    public function getPk();

    public function isVersionEnable();
}
```

实体接口定义了要操作对象的`CRUD`等必备接口, 然后针对不同数据源封装了`MysqlEntityAbstract`, `EsEntityAbstract`, `ApiEntityAbstract`等抽象类, 脚手架控制器中增加了`entity_class`属性

比如, 我们有一个`es` 的索引`goods`存放商品数据

```php
// es model
class EsGoods extend EsBaseModel
{
  // ....
}

// entity 
class EsGoodsEntity extend EsEntityAbstract
{
  // ....
}

// 将实体注入进脚手架
class GoodsController extend AdminAbstractController
{
  	public $entity_class = EsGoodsEntity::class;
}
```

如上操作, 我们将可以通过脚手架完成`es`数据的管理, 而且, 所有的页面效果, 搜索支持等几乎与`mysql`无异.

当然, 我们还可以继续简化以上操作

```php
// es model
class EsGoods extend EsBaseModel
{
  // ....
}

// 将 model 注入进脚手架
class GoodsController extend AdminAbstractController
{
  	public $model_class = EsGoods::class;
}
```

(⊙o⊙)… $model_class 不是用来存放 mysql 模型名称的吗, 怎么放了 es 的 modle 名? 看如下源码

```php
public function getEntity()
{
  if ($this->entity_class) {
    return make($this->entity_class);
  }
  if ($this->model_class && make($this->model_class) instanceof BaseModel) {
    return new class ($this->model_class) extends MysqlEntityAbstract {};
  }
  if ($this->model_class && make($this->model_class) instanceof EsBaseModel) {
    return new class ($this->model_class) extends EsEntityAbstract {};
  }

  return null;
}
```

我们通过匿名类的方式动态生成实体, 进一步简化了操作, 但脚手架最终使用的还是`entity`对象

通过以上形式的抽象, 我们可以实现`ApiEntityAbstract`, `MongoEntityAbstract`, `RedisListEntityAbstract` 等等任意可以操作的数据源了, 而且使用上跟之前完全一样.  将所有数据源上的区别都隐藏在`***EntityAbstract` 中.

看, 就是这么方便.

