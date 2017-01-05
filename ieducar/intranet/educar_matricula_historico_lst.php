<?php

/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Prefeitura Municipal de Itajaí <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Arquivo disponível desde a versão 1.0.0
 * @version   $Id$
 */

require_once 'include/clsBase.inc.php';
require_once 'include/clsListagem.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';
require_once 'lib/Portabilis/String/Utils.php';
require_once 'App/Model/MatriculaSituacao.php';

/**
 * clsIndexBase class.
 *
 * @author    Prefeitura Municipal de Itajaí <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe disponível desde a versão 1.0.0
 * @version   @@package_version@@
 */
class clsIndexBase extends clsBase
{
  function Formular()
  {
    $this->SetTitulo($this->_instituicao . ' i-Educar - Matricula Turma');
    $this->processoAp = 578;
    $this->addEstilo("localizacaoSistema");
  }
}

/**
 * indice class.
 *
 * @author    Prefeitura Municipal de Itajaí <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe disponível desde a versão 1.0.0
 * @version   @@package_version@@
 */
class indice extends clsListagem
{
  var $pessoa_logada;
  var $ref_cod_matricula;

  function Gerar()
  {
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    session_write_close();

    $this->titulo = Portabilis_String_Utils::toLatin1('Lista de enturmações da matrícula');

    $this->ref_cod_matricula = $_GET['ref_cod_matricula'];

    if (!$this->ref_cod_matricula) {
      header('Location: educar_matricula_historico_lst.php');
      die;
    }

    $obj_matricula = new clsPmieducarMatricula($this->ref_cod_matricula);
    $det_matricula = $obj_matricula->detalhe();

    $situacao = App_Model_MatriculaSituacao::getSituacao($det_matricula['aprovado']);

    $this->ref_cod_curso = $det_matricula['ref_cod_curso'];

    $this->ref_cod_serie  = $det_matricula['ref_ref_cod_serie'];
    $this->ref_cod_escola = $det_matricula['ref_ref_cod_escola'];
    $this->ref_cod_turma  = $_GET['ref_cod_turma'];
    $this->ano_letivo     = $_GET['ano_letivo'];

    $this->addCabecalhos(array(
      'Sequencial',
      'Turma',
      'Ativo',
      Portabilis_String_Utils::toLatin1('Data de enturmação'),
      Portabilis_String_Utils::toLatin1('Data de saída'),
      'Transferido',
      'Remanejado',
      'Reclassificado',
      'Abandono',
      Portabilis_String_Utils::toLatin1('Usuário criou'),
      Portabilis_String_Utils::toLatin1('Usuário editou')
    ));

    // Busca dados da matricula
    $obj_ref_cod_matricula = new clsPmieducarMatricula();
    $detalhe_matricula = array_shift($obj_ref_cod_matricula->lista($this->ref_cod_matricula));

    $obj_aluno = new clsPmieducarAluno();
    $det_aluno = array_shift($obj_aluno->lista($detalhe_matricula['ref_cod_aluno'],
      NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1));

    $obj_escola = new clsPmieducarEscola($this->ref_cod_escola, NULL, NULL,
      NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1);
      $det_escola = $obj_escola->detalhe();

    if ($det_escola['nome']) {
      $this->campoRotulo('nm_escola', 'Escola', $det_escola['nome']);
    }

    $this->campoRotulo('nm_pessoa', 'Nome do Aluno', $det_aluno['nome_aluno']);
    $this->campoRotulo('matricula', Portabilis_String_Utils::toLatin1('Matrícula'), $this->ref_cod_matricula);
    $this->campoRotulo('situacao', Portabilis_String_Utils::toLatin1('Situação'), $situacao);
    $this->campoRotulo('data_saida', Portabilis_String_Utils::toLatin1('Data saída'), dataToBrasil($detalhe_matricula['data_cancel']));

    //Paginador
    $this->limite = 20;
    $this->offset = ( $_GET["pagina_{$this->nome}"] ) ? $_GET["pagina_{$this->nome}"]*$this->limite-$this->limite: 0;

    $obj = new clsPmieducarMatriculaTurma();
    $obj->setOrderby( "sequencial ASC" );
    $obj->setLimite( $this->limite, $this->offset );

    $lista = $obj->lista($this->ref_cod_matricula);

    $total = $obj->_total;

    // monta a lista
    if( is_array( $lista ) && count( $lista ) )
    {
      foreach ( $lista AS $registro )
      {
        $ativo = $registro["ativo"] ? 'Sim' : Portabilis_String_Utils::toLatin1('Não');
        $dataEnturmacao = dataToBrasil($registro["data_enturmacao"]);
        $dataSaida = dataToBrasil($registro["data_exclusao"]);
        $dataSaidaMatricula = dataToBrasil($detalhe_matricula["data_cancel"]);
        $transferido = $registro["transferido"] == 't' ? 'Sim' : Portabilis_String_Utils::toLatin1('Não');
        $remanejado = $registro["remanejado"] == 't' ? 'Sim' : Portabilis_String_Utils::toLatin1('Não');
        $abandono = $registro["abandono"] == 't' ? 'Sim' : Portabilis_String_Utils::toLatin1('Não');
        $reclassificado = $registro["reclassificado"] == 't' ? 'Sim' : Portabilis_String_Utils::toLatin1('Não');

        $usuarioCriou = new clsPessoa_($registro['ref_usuario_cad']);
        $usuarioCriou = $usuarioCriou->detalhe();

        $usuarioEditou = new clsPessoa_($registro['ref_usuario_exc']);
        $usuarioEditou = $usuarioEditou->detalhe();

        $this->addLinhas(
          array(
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$registro["sequencial"]}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$registro["nm_turma"]}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$ativo}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$dataEnturmacao}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$dataSaida}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$transferido}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$remanejado}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$reclassificado}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$abandono}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$usuarioCriou['nome']}</a>",
          "<a href=\"educar_matricula_historico_cad.php?ref_cod_matricula={$registro["ref_cod_matricula"]}&ref_cod_turma={$registro["ref_cod_turma"]}&sequencial={$registro["sequencial"]}  \">{$usuarioEditou['nome']}</a>",
          ));
      }
    }
    $this->addPaginador2( "educar_matricula_historico_lst.php", $total, $_GET, $this->nome, $this->limite );

    $this->acao = "go(\"educar_matricula_det.php?cod_matricula={$this->ref_cod_matricula}\")";
    $this->nome_acao = "Voltar";

    $this->largura = "100%";

    $localizacao = new LocalizacaoSistema();
    $localizacao->entradaCaminhos( array(
         $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
         "educar_index.php"                  => "i-Educar - Escola",
         ""                                  => "Listagem de enturma&ccedil;&otilde;es da matr&iacute;cula"
    ));
    $this->enviaLocalizacao($localizacao->montar());
  }
}

// Instancia objeto de página
$pagina = new clsIndexBase();

// Instancia objeto de conteúdo
$miolo = new indice();

// Atribui o conteúdo à  página
$pagina->addForm($miolo);

// Gera o código HTML
$pagina->MakeAll();
?>
<script type="text/javascript">
document.getElementById('botao_busca').style.visibility = 'hidden';
</script>
