<?php

namespace app;
require_once __DIR__ . '/object.php';
require_once __DIR__ . '/Sequelize.php';
use app\ArrayObject;

class Directory extends ArrayObject
{
    /**for Graphql consume */
    public function schemeTypes($name, $data)
    {
        $nm = $this->toUpperName($name);
        $dt = $this->toType($data, $name);
        return "
            type $nm {
           $dt
        }";
    }
    public function schemeQuery($name, $data)
    {
        $nm = $this->toUpperName($name)."Model";
        return "
            all$nm:[$nm!]! @paginate(defaultCount:20)
            find$nm(id: ID @eq): $nm @find
            search$nm(
                name: String! @where(operator: 'like')
                limit: Int @limit
            ): [$nm!]! @all
        ";
    }
    public function schemeMutation($name, $data)
    {
        $nm = $this->toUpperName($name)."Model";
        return $this->toMutation($data, $nm);
    }
    public function toUpperName($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
    /**for API consume */
    function controller($name, $data)
    {
        // static
        $request = '$request';
        $nameModel = "{$this->toUpperName($name)}Model";
        $nameController = $this->toUpperName($name) . 'Controller';
        $id = '$id';
        $name = '$' . $this->toUpperName($name);
        $dt = $this->toObject($data);
        $dv = '$dt';
        $dtId = '$dt->id';
        $save = '$dt->save()';
        $useModel = 'use App\\Models\\' . $nameModel . ';';
        $del = '$dt->delete()';
        $valid = '$this->validate';
        //
        $photos = '';
        $filename =
            '$filename = $request->available."-" . date("d-m-Y") . "-" . time() . "." . $photo->getClientOriginalExtension();';
        $location = '$location = public_path("images/". $filename);';
        $create = 'Image::make($photo)->save($location);';
        //
        $withs = '';
        $validItem = "$valid($request, array(";
        foreach ($data as $c) {
            if ($c->nullable !== 'true') {
                $validItem .= '"' . $c->name . '"';
                $validItem .= '=> "required"';

                $validItem .= ',';
            }
            if ($c->image == 'true') {
                $p = '$request->file("file");';
                $photos .= $p;
                $photos .= $filename;
                $photos .= $location;
                $photos .= $create;
                $useModel .= 'use Image; use File;';
            }
            if ($c->typeData === 'foreignId' && $c->relasi !== 'null') {
                $useModel .= "use App\\model\\{$this->toUpperName(
                    $c->relasi
                )}Model;";
                $withs .= "'{$this->toUpperName($c->relasi)}',";
            }
        }
        $validItem .= '));';

        $isWith = $withs !== '' ? "->with([$withs])" : '';
        // end static
        return "<?php
        
        namespace App\Http\Controllers\Api;
        use App\Http\Controllers\Controller;
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\Validator;
        $useModel
        use Carbon\Carbon;
        class $nameController extends Controller
        {
            public function index()
            {
                $name = $nameModel::orderby('id', 'desc')$isWith" .
            "->get();
                return response()->json([
                    'data' => $name,
                    'message' => 'Data berhasil diambil.'
                ]);
            }

            public function store(Request $request)
            {
                $validItem
                $dv = new $nameModel;
                $dt
                $save;
                $" .
            "$nameController = $nameModel::where('id', $dtId)$isWith" .
            "->first();
                return response()->json([
                    'data' => $dv,
                    'message' => 'Data berhasil disimpan.'
                ]);
            }

            public function show($id)
            {
                $name = $nameModel::where('id', $id)$isWith" .
            "->first();
                return response()->json([
                    'data' => $name,
                    'message' => 'Data berhasil diambil.'
                ]);
            }

            public function update(Request $request, $id)
            {
                $validItem;
                $" .
            "dt = $nameModel::findOrFail($id);
                $dt
                $save;
                return response()->json([
                    'data' => $dv,
                    'message' => 'Data berhasil diperbarui.'
                ]);
            }

            public function destroy($id)
            {
                $" .
            "dt = $nameModel::where('id', $id)->first();
                $del;
                return response()->json([
                    'message' => 'Data berhasil dihapus.'
                ]);
            }
        }
        ";
        return json_encode($dt);
    }
    function model($name, $data)
    {
        $model = $this->toUpperName($name) . 'Model';
        $fillable = 'protected $fillable = [';
        $it = '$this';
        $relasi = '';
        $used = '';
        $last = [];
        foreach ($data as $c) {
            $mode = $c->mode;
            $fillable .= "'" . $c->name . "',";
            $valid =
                $c->relasi !== 'undefined' &&
                $c->relasi !== 'false' &&
                $c->relasi !== 'null' &&
                !in_array($c->name, $last);

            if ($valid) {
                $relasi .=
                    "public function $c->relasi(){
                    return $it->" .
                    $c->mode .
                    "({$this->toUpperName($c->relasi)}" .
                    "Model::class, '$c->name', 'id');
                }";
                $modelRel = $this->toUpperName($c->relasi);
                $used .= "use App\\model\\{$modelRel}Model;";
                array_push($last, $c->name);
            }
        }
        $fillable .= '];';
        $table = '$table';
        return "<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
$used
class $model extends Model
{
    protected $table = '$name';
    $fillable

    $relasi
}";
    }
    function frontend($name, $data)
    {
        $contents = '';
        $header = '';
        $title = $this->toUpperName($name);
        foreach ($data as $c) {
            $label = $this->toUpperName($c->name);
            $attr = $c->nullable !== 'true' ? 'required :rules="rules"' : '';
            $contents .= "
            <v-col cols='12' md='6'>
                <v-text-field v-model='editedItem.$c->name' $attr label='$label' outlined dense/>
            </v-col>";
            $header .= "{ text: '$label', value: '$c->name' },";
        }
        return "
<template>
    <v-container>
        <v-data-table
            :headers='headers'
            :items='data' :loading='loading' :search='search'
            class='elevation-1 px-md-2 rounded-lg'>
            <template v-slot:top>
                <v-toolbar flat>
                    <v-toolbar-title>$title</v-toolbar-title>
                    <v-divider class='mx-4' vertical></v-divider>
                    <v-text-field v-model='search' label='Search...' class='mt-5' rounded outlined solo placeholder='search'/>
                    <v-spacer></v-spacer>
                    <v-dialog v-model='dialog' max-width='800px' scrollable>
                        <template v-slot:activator='{ on, attrs }'>
                            <v-btn elevation='0' color='primary' dark class='rounded-lg' v-bind='attrs' v-on='on'>
                                New $name
                            </v-btn>
                        </template>
                        <v-card flat class='rounded-lg'>
                            <v-card-title>
                                <span class='headline'>{{ formTitle }}</span>
                            </v-card-title>
                            <v-card-text>
                                <v-form v-model='valid' lazy-validation>
                                    <v-row dense>
                                        $contents
                                    </v-row>
                                </v-form>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color='warning' class='rounded-lg elevation-0' @click='close'>
                                    Cancel
                                </v-btn>
                                <v-btn color='success' class='rounded-lg elevation-0' @click='save'>
                                    Save
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-toolbar>
            </template>
            <template v-slot:item.actions='{ item }'>
                <v-chip pill>
                    <v-icon color='green accent-4' left class='mr-2'@click='editItem(item)'>
                        mdi-pencil
                    </v-icon>
                    <v-divider vertical class='mx-2'/>
                    <v-icon color='red' right @click='deleteItem(item)'>
                        mdi-delete
                    </v-icon>
                </v-chip>
            </template>
            <template v-slot:no-data>
            <v-card flat class='rounded-lg text-center text-capitalize'>
                <v-card-text>
                    <v-icon size='400px' class='d-flex mx-auto my-auto'>mdi-emoticon-sad</v-icon>
                </v-card-text>
                <v-card-subtitle class='px-0'>
                    <div>
                        there is no data for this table
                    </div>
                    <div>
                        click the 'refresh' button below to try to re call data
                    </div>
                </v-card-subtitle>
                <v-card-actions>
                    <v-btn color='primary' class='rounded-lg mx-auto' @click='initialize'>
                        refresh
                    </v-btn>
                </v-card-actions>
            </v-card>
            </template>
            <template v-slot:loading>
                <v-skeleton-loader type='table'></v-skeleton-loader>
            </template>
        </v-data-table>
    </v-container>
</template>

<script>
    import axios from 'axios';
    export default {
        data: () => ({
            dialog: false,
            search:null,
            loading: false,
            valid:true,
            headers: [
                $header
                { text: 'Actions', value: 'actions' }
            ],
            rules: [
                v => !!v || 'field is required',
            ],
            data: [],
            editedIndex: -1,
            editedItem: {},
        }),

        computed: {
            formTitle () {
                return this.editedIndex === -1 ? 'New $name' : 'Edit $name'
            },
        },

        watch: {
            dialog (val) {
                val || this.close()
            },
        },

        created () {
            this.initialize()
        },

        methods: {
            initialize () {
                this.loading=true
                axios.get('/$name')
                .then(res => {
                    this.data = res.data.data
                    this.loading=false
                })
                .catch(e => e)
            },

            editItem (item) {
                this.editedIndex = this.data.indexOf(item)
                this.editedItem = Object.assign({}, item)
                this.dialog = true
            },

            deleteItem (item) {
                const index = this.data.indexOf(item)
                const c = confirm('Are you sure you want to delete this item?')
                if (c){
                    this.loading=true
                    axios.delete('/$name/'+item.id)
                    .then(res => {
                        this.loading=false
                        this.data.splice(index, 1)
                    })
                    .catch(error => error)
                }
            },

            close () {
                this.dialog = false
                this.editedItem = Object.assign({}, this.defaultItem)
                this.editedIndex = -1
                this.loading=false
            },

            save () {
                this.loading=true
                if (this.editedIndex > -1) {
                    const id = this.editedItem.id;
                    axios.put('/$name/'+id, this.editedItem)
                    .then(res => {
                        Object.assign(this.data[this.editedIndex], res.data.data)
                        this.close()
                    })
                    .catch(error => error)
                } else {
                    axios.post('/$name', this.editedItem)
                    .then(res => {
                        this.data.push(res.data.data)
                        this.close()
                    })
                    .catch(error => error)
                }
            },
        },
    }
    </script>";
    }
    function routes($name)
    {
        $nameController = $this->toUpperName($name) . 'Controller';
        return "
    Route::get('$name', 'Api\\$nameController@index');
    Route::get('$name/{id}', 'Api\\$nameController@show');
    Route::post('$name', 'Api\\$nameController@store');
    Route::put('$name/{id}', 'Api\\$nameController@update');
    Route::delete('$name/{id}', 'Api\\$nameController@destroy');
        ";
    }
    function routesFront($name)
    {
        $nameController = $name . 'Controller';
        return "
            {
                path: '/$name',
                name: '$name',
                component: () => import ('../view/$name.vue'),
            },
        ";
    }
    function migrations($name, $data)
    {
        $table = '$table';
        $migrasi = '';
        $relation = '';
        $timestamp = $table . '->timestamps();';
        $id = $table . '->id();';
        $down = "public function down(){Schema::dropIfExists('$name');}";
        foreach ($data as $c) {
            $migrasi .= $table . '->' . $c->typeData . "('" . $c->name . "');"; #. $c->typeData . "('" . $isi . "');";
            if ($c->typeData === 'foreignId' && $c->relasi_id !== 'undefined') {
                $relation .=
                    $table .
                    "->foreign('" .
                    $c->name .
                    "')->references('" .
                    $c->relasi_id .
                    "')->on('" .
                    $c->relasi .
                    "')->onUpdate('cascade')->onDelete('cascade');";
            }
        }
        $relasi = "Schema::table('$name', function (Blueprint $table) {
            $relation
        });}";
        return "
            <?php
            use Illuminate\\Database\\Migrations\\Migration;
            use Illuminate\\Database\\Schema\\Blueprint;
            use Illuminate\\Support\\Facades\\Schema;
            class Create{$this->toUpperName($name)}" .
            "Table extends Migration{
                public function up(){
                    Schema::create('$name', function (Blueprint $table) {
                        $id
                        $migrasi
                        $timestamp                        
                    });
                    $relasi
                    $down
            }";
    }
    function toUP($name)
    {
        return strtoupper($name);
    }
}
