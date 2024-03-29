<?php

namespace pendalf89\redirect;


use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\db\Expression;
use yii\db\Query;

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
 *     Yii::$app->redirect->run();
 * },
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
     * @var bool Ignore query part for requested URL.
     * For requested URL https://example.com?123 "?123" will be omitted while searching source in Database.
     */
    public $ignoreQueryPart = true;

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
        if ($this->has($target, 'source')) {
            throw new Exception("The target url ($target) already exists in source column. Infinite redirect prevented.");
        }
        if (!Yii::$app->{$this->dbComponent}->createCommand()->insert($this->tableName, [
            'source'     => $source,
            'target'     => $target,
            'created_at' => new Expression('NOW()'),
        ])->execute()) {
            throw new Exception("Can not save redirect: $source => $target");
        }
    }

    /**
     * Checks for existing the url in source or target columns
     *
     * @param string $url url for searching
     * @param string $type "source" or "target" column for searching the url
     * @return bool
     */
    public function has($url, $type)
    {
        return (new Query())->from($this->tableName)->where([$type => $url])->exists(Yii::$app->{$this->dbComponent});
    }

    /**
     * Checks for infinite redirects by existing target urls in source column.
     *
     * @return array target urls, that presented in source column
     */
    public function getLoopUrls()
    {
        $result = [];
        foreach ((new Query())->select('target')->from($this->tableName)->each() as $item) {
            if ($this->has($item['target'], 'source')) {
                $result[] = $item['target'];
            }
        }
        return $result;
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
        if ($this->ignoreQueryPart && str_contains($source, '?')) {
            $source = strstr($source, '?', true);
        }

        return Yii::$app->{$this->dbComponent}
            ->createCommand("SELECT `target` FROM `$this->tableName` WHERE `source` = :source", [
                'source' => $source
            ])->queryScalar();
    }
}
