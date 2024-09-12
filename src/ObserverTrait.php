<?php
namespace Terran\YiiObserver;

use yii\base\Event;
use yii\db\ActiveRecord;

trait ObserverTrait
{
    protected static $methodCache = [];
    public $originalAttributes = [];

    public function init()
    {
        parent::init();
        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'recordOriginalAttributes']);
        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'recordOriginalAttributes']);
    }
    public function isDirty($attribute)
    {
        $oldValue = isset($this->originalAttributes[$attribute]) ? $this->originalAttributes[$attribute] : null;
        return $oldValue !== $this->$attribute;
    }

    public function recordOriginalAttributes()
    {
        $this->originalAttributes = $this->getOldAttributes();
    }
    public static function observe($observerClass)
    {
        $eventsMap = [
            'creating' => self::EVENT_BEFORE_INSERT,
            'created' => self::EVENT_AFTER_INSERT,
            'updating' => self::EVENT_BEFORE_UPDATE,
            'updated' => self::EVENT_AFTER_UPDATE,
            'deleting' => self::EVENT_BEFORE_DELETE,
            'deleted' => self::EVENT_AFTER_DELETE,
        ];
        // Can be used with yiithings/yii2-softdelete
        if (method_exists(get_called_class(), 'softDelete')) {
            $eventsMap['deleting'] = 'beforeSoftDelete';
            $eventsMap['deleted'] = 'afterSoftDelete';
            $eventsMap['forceDeleted'] = 'afterForceDelete';
            $eventsMap['restoring'] = 'beforeRestore';
            $eventsMap['restored'] = 'afterRestore';
        }
        // Check if there's cache, if not then get and cache the public methods of the class
        if (!isset(self::$methodCache[$observerClass])) {
            self::$methodCache[$observerClass] = get_class_methods($observerClass);
        }

        $observer = new $observerClass();
        foreach (self::$methodCache[$observerClass] as $method) {
            if (array_key_exists($method, $eventsMap)) {
                Event::on(self::class, $eventsMap[$method], function ($event) use ($observer, $method) {
                    $observer->{$method}($event->sender);
                });
            }
        }
    }
}
