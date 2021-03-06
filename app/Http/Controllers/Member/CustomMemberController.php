<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Igreja;
use App\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class CustomMemberController extends Controller
{
	private $mesPT = [
		'1'=>'Janeiro', 
		'2'=>'Fevereiro', 
		'3'=>'Março', 
		'4'=>'Abril', 
		'5'=>'Maio', 
		'6'=>'Junho', 
		'7'=>'Julho',
		'8'=>'Agosto',
		'9'=>'Setembro',
		'10'=>'Outubro',
		'11'=>'Novembro',
		'12'=>'Dezembro',
	];

	public function __construct()
    {
        $this->middleware('auth');
	}
	
	/**
	 * Listar membros apagados
	 */
	public function indexDeleted()
    {
        $members = Member::onlyTrashed()->orderBy('nome', 'ASC', SORT_REGULAR, true)->paginate(10);
        return view('members.indexDeleted', compact('members'));
	}
	
	/**
	 * Restaurar membros apagados
	 */
	public function restore($id)
    {
		$member = Member::onlyTrashed()
			->find($id);
		$member->restore();

		//dd($member);
		return redirect()->action('Member\MemberController@show', $id);
	}
	

	
	public function find(Request $request)
    {
        $validatedData = $request->validate([
            'nome' => 'filled|string',
        ]);
        $procurar = $nome = $request->nome;

	    // trocar acentos
	    $substituir = array(
	        "a","e","i","o","u","y",
	        "à","á","â","ã","ä",
	        "è","é","ê","ë",
	        "ì","í","î","ï",
	        "ò","ó","ô","õ","ö",
	        "ù","ú","û","ü",
	        "A","E","I","O","U",
	        "À","Á","Â","Ã","Ä",
	        "È","É","Ê","Ë",
	        "Ì","Í","Î",
	        "Ò","Ó","Ô","Õ","Ö",
	        "Ù","Ú","Û","Ü",
	        "ç","Ç","ñ","Ñ");
	    $procurar =  str_replace($substituir,"_", $procurar);

	    //procurar pelas palavras em qualquer lugar do nome
	    $procurar = str_replace(" ","%",$procurar);
		$members = Member::where('nome', 'LIKE', '%'.$procurar.'%')->orderBy('nome')->get();
		        
        return view('members.resultadoProcura', compact('members', 'nome'));
    }

	/**
	 * Função para criar novo usuário. 
	 * A função create (MemberController@create) redireciona 
	 * para a página de procura para evitar usuários duplicados. 
	 * 
	 * Esta função mostra o formulario para criar usuários. 
	 */
	public function new(Request $request) 
    {		
		$igrejas = Igreja::get()->pluck('NomeCidade', 'id');
		
		return view('members.create', compact('igrejas'));
		
		/*
		 * Modelo de return passando dados:
		 * 
		return redirect()
                ->route( 'members.show', $member->id )
				->with(['alert'=>'Obreiro atualizado!', 'alert_type'=>'success']);
		*/
	}
	
	public function aniversariantes($mes = null)
    {
		if(!$mes || $mes < 1 || $mes > 12){
			$mes = Carbon::today()->month;
		}
		$aniversariantes = Member::MonthBirthdays($mes)->get();
		$mesPT = $this->mesPT;
		
        return view('members.niver', compact('aniversariantes', 'mesPT', 'mes'));
	}
	
	public function etiquetasAniversariantes($mes = null)
	{
		// verificar se não foi definido um mes e se o mes esta entre Janeiro (1) e Dezembro (12)
		if(!$mes || $mes < 1 || $mes > 12){
			$mes = Carbon::today()->month;
		}

		// pegar os aniveriantes do referido mes
		$aniversariantes = Member::MonthBirthdaysName($mes)->get();

		// passar os dados de formatação da etiqueta (Letter com 20 etiquetas)
		$identacaoTop=3;
        $identacaoLeft=10;
        $top = [12.7, 38.1, 63.5, 88.9, 114.2, 139.7, 165.1, 190.5, 215.9, 241.3];
        $left = [4, 110.3];
		
		// Criar o arquivo pdf 
        $pdf = PDF::loadView('print.labelPDF20', compact('aniversariantes', 'top', 'left', 'identacaoTop', 'identacaoLeft' ))
            ->setPaper('letter','portrait');

		// Exibir o arquivo PDF na tela para imprimir as etiquetas
		return $pdf->stream('etiqueta.pdf');
	}
}
