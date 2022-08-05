<?php

namespace app;

class Sequelize
{
    static function toUpper($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
    static function type($type, $dt = 'DataTypes')
    {
        $isStr = \in_array($type, ['string', 'text', 'longText', 'mediumText']);
        $isInt = \in_array($type, ['integer', 'bigInteger', 'foreignId']);
        return $dt . ($isInt ? '.INTEGER' : '.TEXT');
    }
    static function init($name)
    {
        return "
          const { Sequelize, DataTypes } = require('sequelize');
          export const types = DataTypes;
          export const connection = new Sequelize('$name', null, null, {
          // the sql dialect of the database
          // currently supported: 'mysql', 'sqlite', 'postgres', 'mssql'
          dialect: 'sqlite',
          // custom host; default: localhost
          // host: 'my.server.tld',
          // for postgres, you can also specify an absolute path to a directory
          // containing a UNIX socket to connect over
          // host: '/sockets/psql_sockets'.
          // custom port; default: dialect default
          // port: 12345,
          // custom protocol; default: 'tcp'
          // postgres only, useful for Heroku
          // protocol: null,
          // disable logging or provide a custom logging function; default: console.log
          logging: false,
          // the storage engine for sqlite
          // - default ':memory:'
          storage: './$name.sqlite',
          // disable inserting undefined values as NULL
          // - default: false
          omitNull: true,

          // a flag for using a native library or not.
          // in the case of 'pg' -- set this to true will allow SSL support
          // - default: false
          native: true,

          // Specify options, which are used when sequelize.define is called.
          // The following example:
          //   define: { timestamps: false }
          // is basically the same as:
          //   Model.init(attributes, { timestamps: false });
          //   sequelize.define(name, attributes, { timestamps: false });
          // so defining the timestamps for each model will be not necessary
          define: {
            underscored: false,
            freezeTableName: false,
            charset: 'utf8',
            dialectOptions: {
              collate: 'utf8_general_ci'
            },
            timestamps: true
          },
          // similar for sync: you can define this to always force sync for models
          sync: { force: true },
          // pool configuration used to pool database connections
          pool: {
            max: 5,
            idle: 30000,
            acquire: 60000,
          },
          // isolation level of each transaction
          // defaults to dialect default
          // isolationLevel: Transaction.ISOLATION_LEVELS.REPEATABLE_READ
        })";
    }
    static function models($data, $name, $table)
    {
        $fields = 'id: {
          type: DataTypes.INTEGER,
          autoIncrement: true,
          primaryKey: true
        },';
        $rel = '';
        $plugins = '';
        foreach ($data as $c) {
            $type = self::type($c->typeData);
            $valid =
                $c->relasi !== 'undefined' &&
                $c->relasi !== 'false' &&
                $c->relasi !== 'null';
            $nullable = $c->nullable == 'true' ? 'true' : 'false';
            $fields .= "$c->name: $type,";
            if ($valid) {
                $relName = self::toUpper($c->relasi);
                $plugins .= "import $relName from './$relName.js'";
                $rel .= "models.{$c->mode}($relName,{
                  foreignKey:{
                    name:'$c->name',
                    onDelete: 'CASCADE',
                    onUpdate: 'CASCADE'
                  }
                })";
            }
        }
        $model = "
          const {Model} = require('sequelize');
          $plugins;
          module.exports = (sequelize, DataTypes) =>{
              class $name extends Model {
                static associate(models){
                  $rel
                }
              }
              $name.init({
                $fields
              },{
                sequelize,
                modelName: '$name',
                tableName: '$table'
              });
              return $name;
          }";
        return (object) [
            'model' => $model,
            'migration' => self::migration($data, $table),
        ];
    }
    static function migration($data, $name)
    {
        $fields = '{id: {
          type: Sequelize.INTEGER,
          autoIncrement: true,
          primaryKey: true,
          allowNull:false
        },';
        foreach ($data as $c) {
            $type = self::type($c->typeData, 'Sequelize');
            $valid =
                $c->relasi !== 'undefined' &&
                $c->relasi !== 'false' &&
                $c->relasi !== 'null';
            $nullable = $c->nullable == 'true' ? 'true' : 'false';
            $fields .= "$c->name:{
              type: $type,
              allowNull: $nullable
            },";
        }
        $fields .= 'createdAt: {
          allowNull: false,
          type: Sequelize.DATE
        },
        updatedAt: {
          allowNull: false,
          type: Sequelize.DATE
        }}';
        return "
          'use strict';
          module.exports = {
            up: async (queryInterface, Sequelize) => {
              await queryInterface.createTable('$name', $fields);
            },
            down: async (queryInterface, Sequelize) => {
              await queryInterface.dropTable('$name');
            }
          };";
    }
}
