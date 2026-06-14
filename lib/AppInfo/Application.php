<?php

namespace OCA\TimeTracker\AppInfo;

use \OCP\AppFramework\App;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\ReportItemMapper;

class Application extends App {


  /**
   * Define your dependencies in here
   */
  public function __construct(array $urlParams=array()){
    parent::__construct('timetracker', $urlParams);

    if (!\class_exists('\OCA\TimeTracker\AppFramework\Db\CompatibleMapper')) {
        if (\class_exists(\OCP\AppFramework\Db\Mapper::class)) {
            \class_alias(\OCP\AppFramework\Db\Mapper::class, 'OCA\TimeTracker\AppFramework\Db\CompatibleMapper');
        } else {
            \class_alias(\OCA\TimeTracker\AppFramework\Db\OldNextcloudMapper::class, 'OCA\TimeTracker\AppFramework\Db\CompatibleMapper');
        }
    }

    $container = $this->getContainer();
    /**
     * Controllers
     */
    
    

    $container->registerService('WorkIntervalMapper', function($c){
      return new WorkIntervalMapper(
        $c->query('ServerContainer')->getDatabaseConnection()
      );
    });
    $container->registerService('ReportItemMapper', function($c){
      return new ReportItemMapper(
        $c->query('ServerContainer')->getDatabaseConnection()
      );
    });

  }
}
