/* CRManager | Mapa de Salas | cole SOMENTE o conteúdo no onExecute da Blank (sem tags PHP).
   Proprietário fixo: 13.
   Campos opcionais (GC, nome curto do hotel, formulário) dependem do esquema real.
   As consultas usam apenas campos identificados no código fornecido.
*/
$prop = 13;
$anoAtual = (int)date('Y');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoAtual;
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
if ($ano < $anoAtual || $ano > $anoAtual + 2) $ano = $anoAtual;
if ($mes < 1 || $mes > 12) $mes = (int)date('n');
$ini = sprintf('%04d-%02d-01', $ano, $mes);
$prox = date('Y-m-d', strtotime($ini.' +1 month'));
$ultimo = date('Y-m-d', strtotime($prox.' -1 day'));
$dias = (int)date('t', strtotime($ini));
if (!function_exists('crEsc')) {
 function crEsc($x) { return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
 function crIds($a) { if (!is_array($a)) return array(); return array_values(array_unique(array_filter(array_map('intval',$a),function($v){return $v>0;}))); }
 function crCor($c) { return preg_match('/^#[0-9a-fA-F]{6}$/',(string)$c) ? $c : '#94a3b8'; }
}
$fHotel=crIds(isset($_GET['hotel'])?$_GET['hotel']:array());
$fStatus=crIds(isset($_GET['status'])?$_GET['status']:array());
$meses = array(1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro');
$sem = array('DOM','SEG','TER','QUA','QUI','SEX','SÁB');

/* Uma consulta para salas, outra para composições, outra para status e outra para ocupações. */
$sqlSalas="SELECT s.idServico,s.idHotel,s.servico,c.idServicoPai
 FROM OrcamentoParametroServicoTipo s
 INNER JOIN OrcamentoParametroServicoCategoria cat ON cat.idCategoria=s.categoria
 LEFT JOIN OrcamentoParametroServicoTipoComposicao c ON c.idServicoFilho=s.idServico
 WHERE s.idProprietario=$prop AND s.status='Ativo' AND cat.filtroMapa='Sim'
 ORDER BY s.idHotel,s.servico,s.idServico";
sc_lookup(dsSalas,$sqlSalas);
$salas=array();$hoteis=array();
if (!empty({dsSalas})) foreach ({dsSalas} as $r) {
 $id=(int)$r[0];$h=(int)$r[1];$hoteis[$h]=$h;
 if ($fHotel && !in_array($h,$fHotel,true)) continue;
 $ch=$h.':'.$id;
 if (!isset($salas[$ch])) $salas[$ch]=array('id'=>$id,'hotel'=>$h,'nome'=>(string)$r[2],'pais'=>array());
 if ((int)$r[3]>0) $salas[$ch]['pais'][(int)$r[3]]=(int)$r[3];
}
ksort($hoteis);
$sqlComp="SELECT idServicoPai,idServicoFilho FROM OrcamentoParametroServicoTipoComposicao";
sc_lookup(dsComp,$sqlComp);
$familias=array();
if (!empty({dsComp})) foreach ({dsComp} as $r) {
 $p=(int)$r[0];$f=(int)$r[1];
 if ($p>0 && $f>0) {$familias[$p][$p]=$p;$familias[$p][$f]=$f;}
}
foreach ($salas as $k=>$s) {
 $set=array($s['id']=>$s['id']);
 if (isset($familias[$s['id']])) $set+=$familias[$s['id']];
 foreach ($s['pais'] as $p) if (isset($familias[$p])) $set+=$familias[$p];
 $salas[$k]['servicos']=$set;
}
$sqlStatus="SELECT st.id,ag.agrupador,ag.corHtml
 FROM AtividadeParametroStatus st
 INNER JOIN AtividadeParametroCategoria cat ON cat.id=st.categoria
 LEFT JOIN AtividadeParametroAgrupador ag ON ag.idAgrupador=st.agrupador
 WHERE cat.modulo='GruposEventos' AND cat.nivel='Completo' AND cat.status='Ativo'
 ORDER BY ag.agrupador,st.id";
sc_lookup(dsStatus,$sqlStatus);
$status=array();
if (!empty({dsStatus})) foreach ({dsStatus} as $r) {
 $status[(int)$r[0]]=array('nome'=>(string)$r[1],'cor'=>crCor($r[2]));
}
/* DISTINCT por ocorrência + atividade. Sem GROUP BY por orçamento: preserva serviços distintos.
   Se existirem várias atividades por orçamento, é necessário definir a regra da atividade vigente. */
$sqlOcup="SELECT os.idServico,os.dataInicio,os.dataFinal,
 a.idOrcamento,a.titulo,a.status,ag.agrupador,ag.corHtml
 FROM OrcamentoServico os
 INNER JOIN OrcamentoParametroServicoCategoria sc ON sc.idCategoria=os.categoria
 INNER JOIN Atividade a ON a.idOrcamento=os.idOrcamento
 INNER JOIN AtividadeParametroStatus st ON st.id=a.status
 INNER JOIN AtividadeParametroCategoria cat ON cat.id=st.categoria
 LEFT JOIN AtividadeParametroAgrupador ag ON ag.idAgrupador=st.agrupador
 WHERE a.idOrcamento>0 AND a.idProprietario=$prop
 AND cat.modulo='GruposEventos' AND cat.nivel='Completo' AND cat.status='Ativo'
 AND sc.filtroMapa='Sim'
 AND os.dataInicio<'$prox' AND os.dataFinal>='$ini'
 ORDER BY os.dataInicio,os.idServico";
sc_lookup(dsOcup,$sqlOcup);
$porServico=array();
if (!empty({dsOcup})) foreach ({dsOcup} as $r) {
 $st=(int)$r[5];if ($fStatus && !in_array($st,$fStatus,true)) continue;
 $start=max($ini,substr((string)$r[1],0,10));
 $end=min($ultimo,substr((string)$r[2],0,10));
 if ($start>$end) continue;
 $serv=(int)$r[0];
 $porServico[$serv][]=array('chave'=>(string)$r[3].':'.$st.':'.(string)$r[1].':'.(string)$r[2],
 'inicio'=>(int)substr($start,8,2),'fim'=>(int)substr($end,8,2),
 'titulo'=>(string)$r[4],'orc'=>(int)$r[3],
 'status'=>(string)$r[6],'cor'=>crCor($r[7]));
}
/* Montagem por linha: intervalos independentes e faixas para evitar sobreposição vertical. */
$linhas=array();
foreach ($salas as $k=>$s) {
 $eventos=array();$vistos=array();
 foreach ($s['servicos'] as $id) foreach (($porServico[$id]??array()) as $o) {
  $ch=$id.':'.$o['chave'].':'.$o['inicio'].':'.$o['fim'];
  if (isset($vistos[$ch])) continue;$vistos[$ch]=true;$eventos[]=$o;
 }
 usort($eventos,function($a,$b){return ($a['inicio']<=>$b['inicio'])?:($a['fim']<=>$b['fim']);});
 $faixas=array();$porDia=array();
 foreach ($eventos as $o) {
  $faixa=0;
  while (isset($faixas[$faixa]) && $faixas[$faixa]>=$o['inicio']) $faixa++;
  $faixas[$faixa]=$o['fim'];$o['faixa']=$faixa;$porDia[$o['inicio']][]=$o;
 }
 $linhas[$k]=array('sala'=>$s,'dias'=>$porDia,'altura'=>max(68,count($faixas)*59+8));
}
echo '<style>
.crm{font:12px Arial,sans-serif;color:#172b49;background:#f5f7fa;padding:12px}.crm *{box-sizing:border-box}
.crm .filtros,.crm .legenda{display:flex;align-items:flex-end;flex-wrap:wrap;gap:12px;background:#fff;border:1px solid #dfe5ec;border-radius:8px;padding:12px;margin-bottom:9px}
.crm label{display:flex;flex-direction:column;gap:4px;font-weight:bold}.crm select{padding:7px;border:1px solid #cbd5e1;border-radius:5px;min-width:110px}
.crm .checks{display:flex;flex-wrap:wrap;gap:7px;max-width:580px}.crm .checks label{display:flex;flex-direction:row;align-items:center;background:#f7f9fc;border:1px solid #e0e6ee;padding:5px;border-radius:4px;font-weight:normal}
.crm button{padding:8px 15px;background:#155eef;color:#fff;border:0;border-radius:5px;cursor:pointer}.crm .legenda{align-items:center}
.crm .ponto{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px}
.crm .janela{overflow:auto;max-height:72vh;border:1px solid #dce3ed;border-radius:6px;background:white}
.crm table{border-spacing:0;border-collapse:separate;table-layout:fixed;width:max-content}
.crm th,.crm td{border-right:1px solid #e1e7ef;border-bottom:1px solid #e1e7ef}
.crm thead th{position:sticky;top:0;z-index:4;background:#f4f6f9;width:82px;min-width:82px;height:43px;text-align:center}
.crm thead th:first-child{left:0;z-index:7;width:220px;min-width:220px;text-align:left;padding:8px;background:#fff}
.crm tbody th{position:sticky;left:0;z-index:3;background:#fff;width:220px;min-width:220px;max-width:220px;text-align:left;padding:8px;white-space:normal}
.crm tbody td{position:relative;width:82px;min-width:82px;vertical-align:top}
.crm .hotel th,.crm .hotel td{background:#edf2f7;height:29px;font-weight:bold}.crm .hotel th{z-index:3}
.crm .dom{color:#e02b2b}.crm .sala-hotel{display:block;font-size:10px;color:#2362a7;margin-bottom:5px}
.crm .cartao{position:absolute;left:3px;z-index:2;padding:6px;border:1px solid var(--cor);background:var(--fundo);border-radius:7px;height:51px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.crm .cartao strong{display:block;overflow:hidden;text-overflow:ellipsis}.crm .cartao small{display:block;margin-top:8px}
</style>';
echo '<div class="crm"><form method="get" class="filtros">';
echo '<label>Ano<select name="ano">';
for($y=$anoAtual;$y<=$anoAtual+2;$y++) echo '<option value="'.$y.'"'.($y===$ano?' selected':'').'>'.$y.'</option>';
echo '</select></label><label>Mês<select name="mes">';
foreach($meses as $n=>$m) echo '<option value="'.$n.'"'.($n===$mes?' selected':'').'>'.crEsc($m).'</option>';
echo '</select></label>';
echo '<label>GC<select disabled title="Filtro de GC ainda não configurado"><option>Todos</option></select></label>';
echo '<label>Hotel<select name="hotel[]" multiple size="3">';
foreach($hoteis as $h) echo '<option value="'.$h.'"'.(in_array($h,$fHotel,true)?' selected':'').'>Hotel #'.$h.'</option>';
echo '</select></label><div><b>Status</b><div class="checks">';
foreach($status as $id=>$st) echo '<label><input type="checkbox" name="status[]" value="'.$id.'"'.(in_array($id,$fStatus,true)?' checked':'').'>'.crEsc($st['nome']?:'Status '.$id).'</label>';
echo '</div></div><button type="submit">Filtrar</button></form><div class="legenda">';
$leg=array();
foreach($status as $st) $leg[$st['nome']]= $st['cor'];
foreach($leg as $nome=>$cor) echo '<span><i class="ponto" style="background:'.$cor.'"></i>'.crEsc($nome).'</span>';
echo '</div><div class="janela"><table><thead><tr><th>Salas / Espaços</th>';
for($d=1;$d<=$dias;$d++) {
 $w=(int)date('w',strtotime(sprintf('%04d-%02d-%02d',$ano,$mes,$d)));
 echo '<th'.($w===0?' class="dom"':'').'>'.$sem[$w].'<div>'.$d.'</div></th>';
}
echo '</tr></thead><tbody>';
$hotelAnterior=null;
foreach($linhas as $linha) {
 $s=$linha['sala'];$h=$s['hotel'];
 if($h!==$hotelAnterior) {
  $hotelAnterior=$h;
  echo '<tr class="hotel"><th>Hotel #'.$h.'</th><td colspan="'.$dias.'"></td></tr>';
 }
 echo '<tr><th><span class="sala-hotel">Hotel #'.$h.'</span>'.crEsc($s['nome']).'</th>';
 for($d=1;$d<=$dias;$d++) {
  echo '<td style="height:'.$linha['altura'].'px">';
  foreach(($linha['dias'][$d]??array()) as $o) {
   $largura=($o['fim']-$o['inicio']+1)*82-6;
   $top=7+$o['faixa']*59;
   $cor=$o['cor'];
   /* background color-mix preserva legibilidade; título tem tooltip completo. */
   echo '<div class="cartao" title="'.crEsc($o['titulo'].' | #'.$o['orc'].' | '.$o['status']).'" style="top:'.$top.'px;width:'.$largura.'px;--cor:'.$cor.';--fundo:color-mix(in srgb,'.$cor.' 18%,white)"><strong>'.crEsc($o['titulo']).' #'.$o['orc'].'</strong><small><i class="ponto" style="background:'.$cor.'"></i>'.crEsc($o['status']).'</small></div>';
  }
  echo '</td>';
 }
 echo '</tr>';
}
echo '</tbody></table></div></div>';
