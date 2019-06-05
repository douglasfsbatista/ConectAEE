<?php

namespace App\Http\Controllers;

use App\User;
use App\Aluno;
use App\Gerenciar;
use App\Perfil;
use App\ForumAluno;
use App\MensagemForumAluno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlunoController extends Controller{

  public static function gerenciar($id_aluno){
    $aluno = Aluno::find($id_aluno);

    $mensagens = MensagemForumAluno::where('forum_aluno_id','=',$aluno->forum->id)->orderBy('id','desc')->take(5)->get();

    return view("aluno.gerenciar",[
      'aluno' => $aluno,
      'mensagens' => $mensagens,
    ]);
  }

  public static function listar(){
    $gerenciars = \Auth::user()->gerenciars;
    $alunos = array();

    foreach($gerenciars as $gerenciar){
      array_push($alunos,$gerenciar->aluno);
    }

    //dd($alunos);

    return view("aluno.listar",[
      'alunos' => $alunos
    ]);
  }

  public function cadastrar(){
      $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA',
                  'PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

      return view("aluno.cadastrar", ['estados' => $estados]);
  }

  public function criar(Request $request){

      $validator = Validator::make($request->all(), [
          'perfil' => ['required'],
          'nome' => ['required','min:2','max:191'],
          'sexo' => ['required'],
          'data_nascimento' => ['required','date','before:today'],
          'logradouro' => ['required'],
          'numero' => ['required','numeric'],
          'bairro' => ['required'],
          'cidade' => ['required'],
          'estado' => ['required'],
      ]);

      if($validator->fails()){
          return redirect()->back()->withErrors($validator->errors())->withInput();
      }

      $aluno = new Aluno();
      $aluno->nome = $request->nome;
      $aluno->sexo = $request->sexo;
      $aluno->data_de_nascimento = $request->data_nascimento;
      $aluno->save();

      $endereco = new Endereco();
      $endereco->numero = $request->numero;
      $endereco->logradouro = $request->logradouro;
      $endereco->bairro = $request->bairro;
      $endereco->cidade = $request->cidade;
      $endereco->estado = $request->estado;

      $pessoa->endereco()->save($endereco);

      $forum = new ForumAluno();
      $forum->aluno_id = $aluno->id;
      $forum->save();

      $gerenciar = new Gerenciar();
      $gerenciar->user_id = \Auth::user()->id;
      $gerenciar->aluno_id = $aluno->id;
      $gerenciar->perfil_id = $request->perfil;
      $gerenciar->isAdministrador = True;
      $gerenciar->save();

      return redirect()->route("aluno.listar")->with('success','O Aluno'.$aluno->nome.'foi cadastrado');
  }

  public function gerenciarPermissoes($id_aluno){
    $aluno = Aluno::find($id_aluno);
    $gerenciars = $aluno->gerenciars;

    return view('permissoes.listar',[
      'aluno' => $aluno,
      'gerenciars' => $gerenciars,
    ]);
  }

  public function cadastrarPermissao($id_aluno){
    $aluno = Aluno::find($id_aluno);
    $perfis = Perfil::where('especializacao','=',NULL)->get();

    return view('permissoes.cadastrar',[
      'aluno' => $aluno,
      'perfis' => $perfis,
    ]);
  }

  public function criarPermissao(Request $request){
    //Validação dos dados
    $rules = array(
      'username' => 'required|exists:users,username',
      'perfil' => 'required',
      'especializacao' => 'required_if:perfil,==,Profissional Externo',
    );
    $messages = array(
      'username.required' => 'Necessário inserir um nome de usuário.',
      'username.exists' => 'O usuário em questão não está cadastrado.',
      'perfil.required' => 'Selecione um perfil.',
      'especializacao.required_if' => 'Necessário inserir uma especialização.',
    );
    $validator = Validator::make($request->all(),$rules,$messages);

    if($validator->fails()){
      return redirect()->back()->withErrors($validator->errors())->withInput();
    }

    //Se já existe a relação
    $user = User::where('username','=',$request->username)->first();
      // $gerenciar = Gerenciar::where('aluno_id','=',$request->aluno)->where('user_id','=',$user->id)->first();
      // if($gerenciar != NULL){
      //   return redirect()->back()->withInput()->with('Fail','O usuário '.$user->name.' já possui permissões.');
      // }

    //Criação do Gerencimento
    $gerenciar = new Gerenciar();
    $gerenciar->user_id = $user->id;
    $gerenciar->aluno_id = (int) $request->aluno;

    $perfil = NULL;
    if($request->perfil == 'Profissional Externo'){
      $perfil = new Perfil();
      $perfil->nome = 'Profissional Externo';
      $perfil->especializacao = $request->especializacao;
      $perfil->save();
    }else{
      $perfil = Perfil::where('nome','=',$request->perfil)->where('especializacao','=',NULL)->first();
    }
    $gerenciar->perfil_id = $perfil->id;
    if($request->exists('isAdministrador')){
      $gerenciar->isAdministrador = $request->isAdministrador;
    }
    //dd($gerenciar);
    $gerenciar->save();

    return redirect()->route(
      'aluno.permissoes',[
        'id_aluno' => $gerenciar->aluno_id,
      ])->with('Success','O usuário '.$user->name.' agora possui permissão.');
  }
}
