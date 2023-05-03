<?php
namespace json;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

class Module extends \yii\base\Module
{
    public function init()
    {
        // Define a custom alias named after the namespace
        Craft::setAlias('@json', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'json\\console\\controllers';
        } else {
            $this->controllerNamespace = 'json\\controllers';
        }

        // Craft::$app->getUrlManager()->addRules([
        //     'json/<slug:[a-z0-9\-]+>' => 'actions/json/json/get-data',
        // ]);
        
        // Event::on(Controller::class, Controller::EVENT_BEFORE_ACTION, function($event) {
        //     $event->controller->enableCsrfValidation = false;
        // });

        parent::init();

        // Custom initialization code goes here...
    }
}