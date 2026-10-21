# Mapa de Salas CRManager — implementação inicial

Aplicação alvo: Scriptcase Blank (PHP, HTML, CSS e JavaScript).

## Referências recebidas
- Código 1: `OrcamentoParametroServicoTipo` e `OrcamentoParametroServicoTipoComposicao`, filtrados por categoria com `filtroMapa = 'Sim'`.
- Código 2: `Atividade`, `OrcamentoServico`, `AtividadeParametroStatus`, `AtividadeParametroCategoria` e `AtividadeParametroAgrupador`.
- Referência visual: matriz de salas (linhas) × dias do mês (colunas), filtros de ano, mês, GC, hotel e status.

## Regras
- A tabela de referência para os status é `AtividadeParametroStatus`; as cores vêm de `AtividadeParametroAgrupador.corHtml`.
- Ocupações devem ser consultadas por interseção de intervalos de datas com o mês.
- Uma ocupação distinta não pode ser agrupada somente por `idOrcamento`.
- A implementação deve preservar as composições reais entre salas.

## Confirmações de esquema necessárias antes de liberar em produção
1. Nome exato da chave primária de `OrcamentoServico` para identificar ocorrências individuais.
2. Campo/tabela do GC e do nome curto e ordenação do hotel.
3. Campo que identifica o registro de atividade correto quando há várias atividades por orçamento.
4. URL/nome da aplicação Scriptcase para abrir o formulário do evento.

Não alterar a branch `main` nem presumir nomes de campos não informados.