<?php

namespace pendalf89\redirect;


use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\db\Expression;

/**
 * Component for convenient URL redirects in Yii2.
 *
 * For using this component you need to create table in DB:
 *
 * ```
 * CREATE TABLE `redirect` (
 *     `source` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
 *     `target` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
 *     `created_at` datetime NOT NULL,
 *     PRIMARY KEY (`source`) USING BTREE,
 *     KEY `created_at` (`created_at`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 * ```
 *
 * Then, you need to add component in your App configuration:
 *
 * ```
 * 'components' => [
 *     'redirect' => 'pendalf89\redirect\Redirect',
 * ],
 * ```
 *
 * And register new event in configuration:
 *
 * ```
 * 'on beforeRequest' => function() {
 *   Yii::$app->redirect->run();
 *   },
 * ```
 *
 * Finally, you can add new redirects to DB:
 *
 * ```
 * Yii::$app->redirect->add('https://example.com/from/', 'https://example.com/to/');
 * ```
 *
 */
class Redirect extends BaseObject
{
    /**
     * @var int Redirect status code
     */
    public $redirectSatusCode = 301;

    /**
     * @var string DB table name
     */
    public $tableName = 'redirect';

    /**
     * @var string Database component ID
     */
    public $dbComponent = 'db';

    /**
     * Performing redirect
     *
     * @return void
     */
    public function run()
    {
        if ($target = $this->getTarget(Yii::$app->request->absoluteUrl)) {
            Yii::$app->response->redirect($target, $this->redirectSatusCode);
            Yii::$app->end();
        }
    }

    /**
     * Adding new redirect to DB
     *
     * @param string $source source URL
     * @param string $target target URL
     * @return void
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function add($source, $target)
    {
        if (!Yii::$app->{$this->dbComponent}->createCommand()->insert($this->tableName, [
            'source'     => $source,
            'target'     => $target,
            'created_at' => new Expression('NOW()'),
        ])->execute()) {
            throw new Exception("Can not save redirect: $source => $target");
        }
    }

    /**
     * Get target URL by source URL
     *
     * @param string $source source URL
     * @return string
     * @throws \yii\db\Exception
     */
    protected function getTarget($source)
    {
        return Yii::$app->{$this->dbComponent}
            ->createCommand("SELECT `target` FROM `$this->tableName` WHERE `source` = :source", [
                'source' => $source
            ])->queryScalar();
    }
}
