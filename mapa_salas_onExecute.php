/* Scriptcase Blank - onExecute. Sem indexacao com colchetes. */
$proprietario = 13;
$anoAtual = (int) date('Y');
$mesAtual = (int) date('n');
$ano = $anoAtual;
$mes = $mesAtual;
if (isset($_GET) && is_array($_GET)) {
    $anoSolicitado = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
    $mesSolicitado = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
    if ($anoSolicitado !== false && $anoSolicitado !== null && $anoSolicitado >= $anoAtual && $anoSolicitado <= $anoAtual + 2) $ano = $anoSolicitado;
    if ($mesSolicitado !== false && $mesSolicitado !== null && $mesSolicitado >= 1 && $mesSolicitado <= 12) $mes = $mesSolicitado;
}
$meses = array(1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro');
$semana = array('DOM','SEG','TER','QUA','QUI','SEX','SÁB');
$inicio = sprintf('%04d-%02d-01', $ano, $mes);
$dias = (int) date('t', strtotime($inicio));
$sqlSalas = "SELECT s.idServico,s.idHotel,s.servico
 FROM OrcamentoParametroServicoTipo s
 INNER JOIN OrcamentoParametroServicoCategoria c ON c.idCategoria=s.categoria
 WHERE s.idProprietario=$proprietario AND s.status='Ativo' AND c.filtroMapa='Sim'
 ORDER BY s.idHotel,s.servico,s.idServico";
sc_lookup(dsSalas, $sqlSalas);
echo '<style>.mapa{font:13px Arial;padding:12px}.mapa form{display:flex;gap:12px;margin-bottom:12px}.mapa .rolagem{overflow:auto;max-height:75vh}.mapa table{border-collapse:collapse;min-width:max-content}.mapa th,.mapa td{border:1px solid #ddd;padding:8px;min-width:75px}.mapa thead th{position:sticky;top:0;background:#eaf0f7}.mapa tbody th{position:sticky;left:0;background:#fff;min-width:220px;text-align:left}.mapa .hotel th,.mapa .hotel td{background:#e8eef8}</style>';
echo '<div class="mapa"><form method="get"><label>Ano <select name="ano">';
for ($y=$anoAtual;$y<=$anoAtual+2;$y++) echo '<option value="'.$y.'"'.($y===$ano?' selected':'').'>'.$y.'</option>';
echo '</select></label><label>Mês <select name="mes">';
foreach ($meses as $numero=>$nome) echo '<option value="'.$numero.'"'.($numero===$mes?' selected':'').'>'.$nome.'</option>';
echo '</select></label><label>GC <select disabled><option>Todos</option></select></label><label>Hotel <select disabled><option>Todos</option></select></label><label>Status <select disabled><option>Todos</option></select></label><button type="submit">Filtrar</button></form>';
echo '<div class="rolagem"><table><thead><tr><th>Salas / Espaços</th>';
for ($d=1;$d<=$dias;$d++) {
    $w=(int)date('w',strtotime(sprintf('%04d-%02d-%02d',$ano,$mes,$d)));
    echo '<th>'.implode('',array_slice($semana,$w,1)).'<div>'.$d.'</div></th>';
}
echo '</tr></thead><tbody>';
$hotelAnterior = null;
if ({dsSalas} !== false && !empty({dsSalas})) {
    foreach ({dsSalas} as $registro) {
        $id = (int) implode('',array_slice($registro,0,1));
        $hotel = (int) implode('',array_slice($registro,1,1));
        $nome = htmlspecialchars((string)implode('',array_slice($registro,2,1)),ENT_QUOTES,'UTF-8');
        if ($hotelAnterior !== $hotel) {
            $hotelAnterior = $hotel;
            echo '<tr class="hotel"><th>Hotel #'.$hotel.'</th><td colspan="'.$dias.'"></td></tr>';
        }
        echo '<tr><th>'.$nome.'</th>';
        for ($d=1;$d<=$dias;$d++) echo '<td style="height:45px"></td>';
        echo '</tr>';
    }
}
echo '</tbody></table></div></div>';
