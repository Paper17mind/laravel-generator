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
        $nm = $this->toUpperName($name) . 'Model';
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
        $nm = $this->toUpperName($name) . 'Model';
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
            <div class='col-12'>
                <q-input v-model='editedItem.$c->name' $attr label='$label' filled rounded dense/>
            </div>";
            $header .= "{ label: '$label', name: '$c->name', field: '$c->name', sortable: true, align:'left' },";
        }
        return "
<template>
	<div>
		<q-table
			:columns='headers'
			:rows='data' :loading='loading' 
			:filter=\"search\"
			class='sticky-table'
			row-key=\"id\"
			flat
			dense
			bordered
			virtual-scroll>
			<template #top>
				<q-bar flat>
					<div>$title</div>
					<q-separtor class='q-mx-md' vertical></q-separtor>
					<q-input v-model='search' label='Search...' rounded filled dense/>
					<q-space></q-space>
					<q-btn elevation='0' color='primary' dark class='rounded-lg' @click=\"dialog=true\">
						New $name
					</q-btn>
				</q-bar>
			</template>
			<template #body-cell-actions='{ row }'>
				<q-btn-group rounded flat>
					<q-btn color='primary' flat round icon='edit' @click='editItem(row)'/>
					<q-btn color='red' flat round @click='deleteItem(row)' icon='delete'/>
				</q-btn-group>
			</template>
			<template #loading>
				<q-inner-loading showing color=\"primary\" />
			</template>
		</q-table>
		<q-dialog v-model='dialog' min-width='50vw' position='bottom'>
			<q-card flat>
				<q-card-section>
					<div class='text-h6'>{{ formTitle }}</div>
				</q-card-section>
				<q-card-section style=\"max-height: 50vh\" class=\"scroll\">
					<div class='row q-gutter-sm'>
						$contents
					</div>
				</q-card-section>
				<q-separator />
				<q-card-section align='right'>
					<q-btn color='warning' flat @click='close'>
						Cancel
					</q-btn>
					<q-btn color='success' flat @click='save'>
						Save
					</q-btn>
				</q-card-section>
			</q-card>
		</q-dialog>
	</div>
</template>

<script>
	import { api } from \"src/boot/axios\";
	import { useQuasar } from 'quasar'
	import {
		computed,
		defineComponent,
		nextTick,
		onMounted,
		ref,
	} from \"@vue/runtime-core\";
    export default defineComponent({
			setup(){
				const \$q = useQuasar()
				const dialog = ref(false);
				const search = ref(null);
				const loading = ref(false);
				const valid = ref(true);
				const headers = ref([
					$header
					{ label: 'Actions', name: 'actions', field: 'actions', align:'right' }
				]);
				const rules = rules: [
					v => !!v || 'field is required',
				];
				const data = ref([]);
				const editedIndex = ref(-1);
				const editedItem = ref({});
				// methods
				function notif(title,color,e){
					\$q.notify({
						message: title,
						caption: e.response && e.response.data ? e.response.data.message : e,
						color: color,
						position:'top-right'
					});
				}
				function initialize () {
					loading.value = true
					api.get('/$name').then(res => {
							data.value = res.data.data
							loading.value = false
						})
						.catch((e) => notif('Error :(', 'red', e))
				}
				
				function editItem(item){
					editedIndex.value = data.value.indexOf(item)
					editedItem.value = Object.assign({}, item)
					dialog.value = true
				}

				function deleteItem (item) {
					const index = data.value.indexOf(item);
					\$q.dialog({
						title: 'Please Confirm',
						message: 'Are you sure you want to delete this item ?',
						cancel: true,
						persistent: true
					}).onOk(() => {
						loading.value = true;
						api.delete('/$name/'+item.id)
							.then(res => {
								loading.value = false
								data.value.splice(index, 1)
							})
							.catch(e => notif('Error :(', 'red', e))
					}).onCancel(() => {});
				}

				function close () {
					dialog.value = false
					editedItem.value = Object.assign({}, this.defaultItem)
					editedIndex.value = -1
					loading.value = false
				}

				function save () {
					this.loading=true
					if (this.editedIndex > -1) {
						const id = editedItem.value.id;
						api.put('/$name/'+id, editedItem.value)
						.then((res) => {
								Object.assign(data.value[this.editedIndex], res.data.data)
								close()
						})
						.catch(e => notif('Error','red', e))
					} else {
						api.post('/$name', editedItem.value)
						.then(res => {
							data.value.push(res.data.data)
							close()
						})
						.catch(e => notif('Error','red', e))
					}
				}
				onMounted(()=> initialize())
				// response
				return {
					dialog,
					search,
					loading,
					valid,
					headers,
					rules,
					data,
					editedIndex,
					editedItem,
					//computed
					formTitle: computed({ get:()=> editedIndex.value === -1 ? 'Create $name' : 'Edit $name' }),
					//methods
					save,
					close,
					editItem,
					deleteItem
				}
			},
			watch: {
				dialog (val) {
					val || this.close()
				},
			},
    })
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
