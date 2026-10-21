/* CRManager - Mapa de Salas
 * Revisao Scriptcase 2026-10-21: chamadas internas de metodos via $this->.
 * O Scriptcase incorpora funcoes do onExecute na classe da aplicacao.
 * Cole no evento onExecute da aplicacao Blank, sem abrir tags PHP.
 * Padrao do mapa anterior: sc_select, fields, MoveNext e Close.
 * Proprietario obtido da variavel global Scriptcase [proprietario].
 */
$crmMapaProprietario = (int) [proprietario];
$this->crmMapaExecutar($crmMapaProprietario);

function crmMapaExecutar($proprietario)
{
    $anoAtual = (int) date('Y');
    $mesAtual = (int) date('n');
    $ano = isset($_POST['ano']) ? (int) $_POST['ano'] : $anoAtual;
    $mes = isset($_POST['mes']) ? (int) $_POST['mes'] : $mesAtual;
    if ($ano < $anoAtual || $ano > $anoAtual + 2) $ano = $anoAtual;
    if ($mes < 1 || $mes > 12) $mes = $mesAtual;

    $filtros = array(
        'ano' => $ano,
        'mes' => $mes,
        'hotel' => isset($_POST['hotel']) ? (int) $_POST['hotel'] : 0,
        'gc' => isset($_POST['gc']) ? (string) $_POST['gc'] : '',
        'status' => array()
    );
    $salas = $this->crmMapaBuscarSalas($proprietario);
    $hoteis = $this->crmMapaBuscarHoteis($proprietario);
    $gcs = $this->crmMapaBuscarGCs($proprietario);
    if ($filtros['gc'] !== '' && !isset($gcs[$filtros['gc']])) $filtros['gc'] = '';
    $status = $this->crmMapaBuscarStatus($proprietario);
    // Primeira abertura: todos marcados. Envio sem marcados: nenhum evento.
    if (!isset($_POST['status_enviado'])) {
        $filtros['status'] = array_map('intval', array_keys($status));
    } else {
        $selecionados = isset($_POST['status']) && is_array($_POST['status']) ? $_POST['status'] : array();
        foreach ($selecionados as $idStatus) {
            $idStatus = (int) $idStatus;
            if (isset($status[$idStatus])) $filtros['status'][$idStatus] = $idStatus;
        }
        $filtros['status'] = array_values($filtros['status']);
    }
    $eventos = $this->crmMapaBuscarEventos($proprietario, $filtros);
    echo $this->crmMapaRenderizar($filtros, $salas, $status, $eventos, $hoteis, $gcs);
}

function crmMapaBuscarGCs($proprietario)
{
    $sql = "SELECT DISTINCT sec_users.login, sec_users.name
            FROM sec_users
            INNER JOIN sec_users_proprietario
                ON sec_users.login = sec_users_proprietario.login
            WHERE sec_users_proprietario.idProprietario = " . (int) $proprietario . "
            ORDER BY sec_users.name";
    sc_select(dsCrmMapaGCs, $sql);
    $gcs = array();
    if ({dsCrmMapaGCs} === false) return $gcs;
    while (!{dsCrmMapaGCs}->EOF) {
        $login = (string) {dsCrmMapaGCs}->fields[0];
        $gcs[$login] = (string) {dsCrmMapaGCs}->fields[1];
        {dsCrmMapaGCs}->MoveNext();
    }
    {dsCrmMapaGCs}->Close();
    return $gcs;
}

function crmMapaBuscarHoteis($proprietario)
{
    $sql = "SELECT idHotel, nome
            FROM Hotel
            WHERE status = 'Ativo'
              AND idProprietario = " . (int) $proprietario . "
            ORDER BY nome";
    sc_select(dsCrmMapaHoteis, $sql);
    $hoteis = array();
    if ({dsCrmMapaHoteis} === false) return $hoteis;
    while (!{dsCrmMapaHoteis}->EOF) {
        $id = (int) {dsCrmMapaHoteis}->fields[0];
        $hoteis[$id] = (string) {dsCrmMapaHoteis}->fields[1];
        {dsCrmMapaHoteis}->MoveNext();
    }
    {dsCrmMapaHoteis}->Close();
    return $hoteis;
}

function crmMapaBuscarSalas($proprietario)
{
    $sql = "SELECT s.idServico, s.idHotel, s.servico, c.idServicoPai
            FROM OrcamentoParametroServicoTipo s
            INNER JOIN OrcamentoParametroServicoCategoria cat
                ON cat.idCategoria = s.categoria
            LEFT JOIN OrcamentoParametroServicoTipoComposicao c
                ON c.idServicoFilho = s.idServico
            WHERE s.idProprietario = " . (int) $proprietario . "
              AND s.status = 'Ativo'
              AND cat.filtroMapa = 'Sim'
            ORDER BY s.idHotel, s.servico, s.idServico";
    sc_select(dsCrmMapaSalas, $sql);
    $salas = array();
    if ({dsCrmMapaSalas} === false) return $salas;
    while (!{dsCrmMapaSalas}->EOF) {
        $id = (int) {dsCrmMapaSalas}->fields[0];
        $hotel = (int) {dsCrmMapaSalas}->fields[1];
        $nome = (string) {dsCrmMapaSalas}->fields[2];
        $pai = (int) {dsCrmMapaSalas}->fields[3];
        $chave = $hotel . ':' . $id;
        if (!isset($salas[$chave])) {
            $salas[$chave] = array('id'=>$id,'hotel'=>$hotel,'nome'=>$nome,'pais'=>array());
        }
        if ($pai > 0) $salas[$chave]['pais'][$pai] = $pai;
        {dsCrmMapaSalas}->MoveNext();
    }
    {dsCrmMapaSalas}->Close();
    return array_values($salas);
}

function crmMapaBuscarStatus($proprietario)
{
    $sql = "SELECT st.id, st.atividadeStatus, ag.corHtml
            FROM AtividadeParametroStatus st
            INNER JOIN AtividadeParametroCategoria cat ON cat.id = st.categoria
            LEFT JOIN AtividadeParametroAgrupador ag ON ag.idAgrupador = st.agrupador
            WHERE cat.modulo = 'GruposEventos'
              AND cat.nivel = 'Completo'
              AND cat.status = 'Ativo'
              AND st.idProprietario = " . (int) $proprietario . "
            GROUP BY st.atividadeStatus
            ORDER BY st.ordenador ASC, st.id ASC";
    sc_select(dsCrmMapaStatus, $sql);
    $status = array();
    if ({dsCrmMapaStatus} === false) return $status;
    while (!{dsCrmMapaStatus}->EOF) {
        $id = (int) {dsCrmMapaStatus}->fields[0];
        $nome = (string) {dsCrmMapaStatus}->fields[1];
        $cor = (string) {dsCrmMapaStatus}->fields[2];
        $status[$id] = array('nome'=>$nome, 'cor'=>$this->crmMapaCor($cor));
        {dsCrmMapaStatus}->MoveNext();
    }
    {dsCrmMapaStatus}->Close();
    return $status;
}

function crmMapaBuscarEventos($proprietario, $filtros)
{
    $inicio = sprintf('%04d-%02d-01', $filtros['ano'], $filtros['mes']);
    $proximo = date('Y-m-d', strtotime($inicio . ' +1 month'));
    $sql = "SELECT os.idServico, os.dataInicio, os.dataFinal,
                   a.idOrcamento, a.titulo, a.status, ag.agrupador, ag.corHtml, oc.numeroOrcamento, a.idAtividade
            FROM OrcamentoServico os
            INNER JOIN OrcamentoParametroServicoCategoria sc ON sc.idCategoria = os.categoria
            INNER JOIN Atividade a ON a.idOrcamento = os.idOrcamento
            INNER JOIN Orcamento oc ON oc.idOrcamento = a.idOrcamento
            INNER JOIN AtividadeParametroStatus st ON st.id = a.status
            INNER JOIN AtividadeParametroCategoria cat ON cat.id = st.categoria
            LEFT JOIN AtividadeParametroAgrupador ag ON ag.idAgrupador = st.agrupador
            WHERE a.idOrcamento > 0
              AND a.idProprietario = " . (int) $proprietario . "
              AND cat.modulo = 'GruposEventos'
              AND cat.nivel = 'Completo'
              AND cat.status = 'Ativo'
              AND sc.filtroMapa = 'Sim'
              " . ($filtros['gc'] !== '' ? " AND oc.responsavelCotacao = '" . addslashes($filtros['gc']) . "'" : "") . "
              AND os.dataInicio < '" . $proximo . "'
              AND os.dataFinal >= '" . $inicio . "'
            ORDER BY os.dataInicio, os.idServico";
    sc_select(dsCrmMapaEventos, $sql);
    $eventos = array();
    if ({dsCrmMapaEventos} === false) return $eventos;
    while (!{dsCrmMapaEventos}->EOF) {
        $statusId = (int) {dsCrmMapaEventos}->fields[5];
        if (in_array($statusId, $filtros['status'], true)) {
            $eventos[] = array(
                'servico'=>(int){dsCrmMapaEventos}->fields[0],
                'inicio'=>substr((string){dsCrmMapaEventos}->fields[1],0,10),
                'fim'=>substr((string){dsCrmMapaEventos}->fields[2],0,10),
                'orcamento'=>(int){dsCrmMapaEventos}->fields[3],
                'numeroOrcamento'=>(string){dsCrmMapaEventos}->fields[8],
                'idAtividade'=>(int){dsCrmMapaEventos}->fields[9],
                'titulo'=>(string){dsCrmMapaEventos}->fields[4],
                'status'=>(string){dsCrmMapaEventos}->fields[6],
                'cor'=>$this->crmMapaCor({dsCrmMapaEventos}->fields[7])
            );
        }
        {dsCrmMapaEventos}->MoveNext();
    }
    {dsCrmMapaEventos}->Close();
    return $eventos;
}

function crmMapaCor($cor)
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$cor) ? $cor : '#94a3b8';
}
function crmMapaEsc($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function crmMapaRenderizar($filtros, $salas, $status, $eventos, $hoteis, $gcs)
{
    $anoAtual = (int)date('Y');
    $meses = array(1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                   7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro');
    $semana = array('DOM','SEG','TER','QUA','QUI','SEX','SÁB');
    $primeiro = sprintf('%04d-%02d-01', $filtros['ano'], $filtros['mes']);
    $ultimo = date('Y-m-t', strtotime($primeiro));
    $dias = (int)date('t', strtotime($primeiro));

    $html = '<style>
    .crm-mapa{font:12px Arial,sans-serif;background:#f5f7fa;padding:12px;color:#24344b}
    .crm-mapa *{box-sizing:border-box}
    .crm-filtros{display:flex;align-items:flex-start;justify-content:center;flex-wrap:wrap;gap:10px;background:#fff;padding:12px;border:1px solid #e2e7ef;border-radius:8px;margin-bottom:9px}
    .crm-filtros > label{display:flex;flex-direction:column;gap:5px;font-weight:bold;padding-top:0}
    .crm-filtros select{padding:7px;border:1px solid #cbd5e1;border-radius:5px}
    .crm-status-filtro{display:flex;flex-direction:column;gap:5px;min-width:270px}
    .crm-status-titulo{font-weight:bold}
    .crm-status-opcoes{display:grid;grid-template-columns:repeat(3,minmax(110px,1fr));gap:4px}
    .crm-filtros .crm-status-item{display:flex;flex-direction:row;align-items:center;gap:5px;padding:5px 7px;border:1px solid #d8e0ea;background:#f7f9fc;border-radius:5px;font-weight:normal;font-size:10px;white-space:nowrap;cursor:pointer}
    .crm-status-item input{margin:0;accent-color:#155eef}
    @media(max-width:650px){.crm-status-opcoes{grid-template-columns:repeat(2,minmax(110px,1fr))}}
    .crm-legenda{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:9px}
    .crm-legenda span{background:white;padding:6px;border-radius:4px}
    .crm-bola{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px}
    .crm-scroll{overflow:auto;height:calc(100dvh - 185px);min-height:350px;background:white;border:1px solid #e2e7ef}
    .crm-grade{min-width:max-content}
    .crm-cabecalho,.crm-linha,.crm-hotel{display:grid;grid-template-columns:220px repeat(var(--crm-dias),90px);position:relative}
    .crm-cabecalho{position:sticky;top:0;z-index:15;background:#edf2f7}
    .crm-nome{position:sticky;left:0;z-index:5;background:white;padding:9px;border-right:1px solid #ddd}
    .crm-cabecalho .crm-nome{z-index:20;background:#edf2f7}
    .crm-dia{border-right:1px solid #e5eaf0;border-bottom:1px solid #e5eaf0;min-height:55px;text-align:center;padding-top:8px}
    .crm-cabecalho .crm-dia{min-height:45px}
    .crm-hotel{background:#e8f0fb;font-weight:bold;min-height:30px}
    .crm-hotel .crm-nome{background:#e8f0fb;color:#1558a6;font-size:13px;font-weight:700;text-transform:uppercase;line-height:1.3}
    .crm-linha{min-height:75px;border-bottom:1px solid #e5eaf0}
    .crm-linha .crm-dia{min-height:75px}
    .crm-linha .crm-nome{font-size:10px;font-weight:700;line-height:1.35}
    .crm-cartao{position:absolute;z-index:2;top:var(--crm-top);left:calc(220px + (var(--crm-inicio) - 1)*90px + 3px);width:calc(var(--crm-duracao)*90px - 6px);height:54px;background:color-mix(in srgb,var(--crm-cor) 12%,white);border:1px solid var(--crm-cor);border-radius:9px;padding:8px 6px;overflow:hidden;color:#123044;font-size:10px}
    .crm-cartao:hover{filter:brightness(.98);box-shadow:0 2px 6px rgba(0,0,0,.12)}
    .crm-cartao:focus-visible{outline:2px solid #155eef;outline-offset:2px}
    .crm-cartao-topo{display:flex;align-items:center;justify-content:space-between;gap:3px;min-width:0}
    .crm-cartao-titulo{font-size:10px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
    .crm-cartao-numero{font-size:9px;font-weight:700;color:#64748b;white-space:nowrap;flex-shrink:0}
    .crm-cartao-status{display:flex;align-items:center;gap:4px;font-size:9px;font-weight:600;margin-top:8px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
    .crm-cartao-bola{width:7px;height:7px;border-radius:50%;background:var(--crm-cor);flex-shrink:0}
    .crm-modal{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.55)}
    .crm-modal.crm-modal-aberto{display:flex}
    .crm-modal-janela{width:min(1200px,96vw);height:min(850px,94dvh);background:white;border-radius:10px;box-shadow:0 15px 55px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden}
    .crm-modal-barra{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 14px;background:#f1f5f9;font-weight:bold;font-size:13px}
    .crm-modal-fechar{border:0;background:#e2e8f0;border-radius:5px;padding:6px 12px;cursor:pointer;font-weight:bold}
    .crm-modal-iframe{width:100%;height:100%;border:0;flex:1}
    </style>';
    $html .= '<div class="crm-mapa"><form class="crm-filtros" method="post" onchange="this.requestSubmit ? this.requestSubmit() : this.submit()"><label>Ano<select name="ano">';
    for ($ano=$anoAtual;$ano<=$anoAtual+2;$ano++) {
        $html .= '<option value="'.$ano.'"'.($ano===$filtros['ano']?' selected':'').'>'.$ano.'</option>';
    }
    $html .= '</select></label><label>Mês<select name="mes">';
    foreach ($meses as $numero=>$nome) {
        $html .= '<option value="'.$numero.'"'.($numero===$filtros['mes']?' selected':'').'>'.$nome.'</option>';
    }
    $html .= '</select></label><label>GC<select name="gc"><option value="">Todos</option>';
    foreach ($gcs as $login=>$nome) {
        $html .= '<option value="'.$this->crmMapaEsc($login).'"'.($filtros['gc']===$login?' selected':'').'>'.$this->crmMapaEsc($nome).'</option>';
    }
    $html .= '</select></label>';
    $html .= '<label>Hotel<select name="hotel"><option value="0">Todos</option>';
    foreach ($hoteis as $id=>$nome) {
        $html .= '<option value="'.$id.'"'.($filtros['hotel']===(int)$id?' selected':'').'>'.$this->crmMapaEsc($nome).'</option>';
    }
    $html .= '</select></label><div class="crm-status-filtro"><span class="crm-status-titulo">Status</span><div class="crm-status-opcoes"><input type="hidden" name="status_enviado" value="1">';
    foreach ($status as $id=>$item) {
        $marcado = in_array((int)$id, $filtros['status'], true) ? ' checked' : '';
        $html .= '<label class="crm-status-item"><input type="checkbox" name="status[]" value="'.(int)$id.'"'.$marcado.'><span>'.$this->crmMapaEsc($item['nome']).'</span></label>';
    }
    $html .= '</div></div></form><div class="crm-legenda">';
    $legenda = array();
    foreach ($status as $item) $legenda[$item['nome']] = $item['cor'];
    foreach ($legenda as $nome=>$cor) $html .= '<span><i class="crm-bola" style="background:'.$cor.'"></i>'.$this->crmMapaEsc($nome).'</span>';
    $html .= '</div><div class="crm-scroll"><div class="crm-grade" style="--crm-dias:'.$dias.'">';
    $html .= '<div class="crm-cabecalho"><div class="crm-nome">Salas / Espaços</div>';
    for ($d=1;$d<=$dias;$d++) {
        $w=(int)date('w',strtotime(sprintf('%04d-%02d-%02d',$filtros['ano'],$filtros['mes'],$d)));
        $html .= '<div class="crm-dia">'.$semana[$w].'<div><b>'.$d.'</b></div></div>';
    }
    $html .= '</div>';
    $hotelAnterior = null;
    foreach ($salas as $sala) {
        if ($filtros['hotel'] > 0 && $filtros['hotel'] !== $sala['hotel']) continue;
        if ($hotelAnterior !== $sala['hotel']) {
            $hotelAnterior = $sala['hotel'];
            $html .= '<div class="crm-hotel"><div class="crm-nome">'.$this->crmMapaEsc(isset($hoteis[$hotelAnterior]) ? $hoteis[$hotelAnterior] : 'Hotel #'.$hotelAnterior).'</div></div>';
        }
        $daSala = array();
        foreach ($eventos as $evento) {
            if ($evento['servico'] === $sala['id']) $daSala[] = $evento;
        }
        $faixas = array();
        $cartoes = '';
        foreach ($daSala as $evento) {
            $dataIni=max($primeiro,$evento['inicio']);
            $dataFim=min($ultimo,$evento['fim']);
            if ($dataIni > $dataFim) continue;
            $diaIni=(int)substr($dataIni,8,2);
            $diaFim=(int)substr($dataFim,8,2);
            $faixa=0;
            while (isset($faixas[$faixa]) && $faixas[$faixa] >= $diaIni) $faixa++;
            $faixas[$faixa]=$diaFim;
            $top=8+$faixa*60;
            $cartoes .= '<a class="crm-cartao" href="https://www.ftsolution.myhoost.com/scriptcase/app/crmanagerV1/AtividadeRegistro/index.php?idAtiv='.(int)$evento['idAtividade'].'" data-crm-modal="1" style="--crm-inicio:'.$diaIni.';--crm-duracao:'.($diaFim-$diaIni+1).';--crm-top:'.$top.'px;--crm-cor:'.$evento['cor'].';text-decoration:none;cursor:pointer" title="'.$this->crmMapaEsc($evento['titulo']).' - #'.$this->crmMapaEsc($evento['numeroOrcamento']).'">'
                .'<div class="crm-cartao-topo"><span class="crm-cartao-titulo">'.$this->crmMapaEsc($evento['titulo']).'</span><span class="crm-cartao-numero">#'.$this->crmMapaEsc($evento['numeroOrcamento']).'</span></div>'
                .'<div class="crm-cartao-status"><i class="crm-cartao-bola"></i><span>'.$this->crmMapaEsc($evento['status']).'</span></div></a>';
        }
        $altura=max(75,count($faixas)*60+10);
        $html .= '<div class="crm-linha" style="min-height:'.$altura.'px"><div class="crm-nome">'.$this->crmMapaEsc($sala['nome']).'</div>';
        for ($d=1;$d<=$dias;$d++) $html .= '<div class="crm-dia" style="min-height:'.$altura.'px"></div>';
        $html .= $cartoes.'</div>';
    }
    $html .= '</div></div></div>';
    $html .= '<div id="crm-modal-atividade" class="crm-modal" role="dialog" aria-modal="true" aria-label="Registro de atividade" aria-hidden="true"><div class="crm-modal-janela"><div class="crm-modal-barra"><span>Registro de atividade</span><button type="button" class="crm-modal-fechar" id="crm-modal-fechar">Fechar ×</button></div><iframe class="crm-modal-iframe" id="crm-modal-iframe" title="Registro de atividade" src="about:blank"></iframe></div></div>';
    $html .= '<script>
    (function(){
        var modal=document.getElementById("crm-modal-atividade");
        var frame=document.getElementById("crm-modal-iframe");
        var fechar=document.getElementById("crm-modal-fechar");
        if(!modal || !frame || !fechar)return;
        function abrir(url){frame.src=url;modal.classList.add("crm-modal-aberto");modal.setAttribute("aria-hidden","false");document.body.style.overflow="hidden";fechar.focus();}
        function encerrar(){modal.classList.remove("crm-modal-aberto");modal.setAttribute("aria-hidden","true");frame.src="about:blank";document.body.style.overflow="";}
        document.addEventListener("click",function(e){
            var link=e.target.closest("a[data-crm-modal]");
            if(link){e.preventDefault();abrir(link.href);}
        });
        fechar.addEventListener("click",encerrar);
        modal.addEventListener("click",function(e){if(e.target===modal)encerrar();});
        document.addEventListener("keydown",function(e){if(e.key==="Escape" && modal.classList.contains("crm-modal-aberto"))encerrar();});
    })();
    </script>';
    return $html;
}
