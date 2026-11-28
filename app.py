import csv
import html
import io
import os
import re
import secrets
import sqlite3
import sys
import webbrowser
from contextlib import contextmanager
from datetime import date
from decimal import Decimal, InvalidOperation
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlencode, urlsplit

ROOT = Path(__file__).resolve().parent
DB = ROOT / "dados" / "abastecimentos.sqlite"
TOKEN = secrets.token_urlsafe(32)
PORT = 8011
@contextmanager
def banco():
    DB.parent.mkdir(exist_ok=True)
    con = sqlite3.connect(DB)
    con.row_factory = sqlite3.Row
    try:
        with con:
            yield con
    finally:
        con.close()

def iniciar():
    with banco() as con:
        con.executescript("""CREATE TABLE IF NOT EXISTS notas(
        id INTEGER PRIMARY KEY, data TEXT NOT NULL, placa TEXT NOT NULL, modelo TEXT NOT NULL,
        motorista TEXT NOT NULL, posto TEXT NOT NULL, km INTEGER NOT NULL,
        diesel INTEGER NOT NULL, valor INTEGER NOT NULL, arla INTEGER NOT NULL, valorarla INTEGER NOT NULL,
        observacao TEXT NOT NULL, chave TEXT UNIQUE NOT NULL);
        CREATE INDEX IF NOT EXISTS placa_data ON notas(placa,data,km,id);
        CREATE TABLE IF NOT EXISTS cadastros(
            id INTEGER PRIMARY KEY, tipo TEXT NOT NULL, nome TEXT NOT NULL, chave TEXT NOT NULL,
            detalhe TEXT NOT NULL DEFAULT '', observacao TEXT NOT NULL DEFAULT '', UNIQUE(tipo,chave));""")
        con.execute('BEGIN IMMEDIATE')
        if 'parcial' not in {r['name'] for r in con.execute('PRAGMA table_info(notas)')}:
            con.execute('ALTER TABLE notas ADD COLUMN parcial INTEGER NOT NULL DEFAULT 0')
        existentes = {r['name'] for r in con.execute('PRAGMA table_info(cadastros)')}
        if 'motorista_id' not in existentes:
            con.execute('ALTER TABLE cadastros ADD COLUMN motorista_id INTEGER REFERENCES cadastros(id)')
        for campos in EXTRAS.values():
            for coluna, _ in campos:
                if coluna not in existentes:
                    con.execute(f"ALTER TABLE cadastros ADD COLUMN {coluna} TEXT NOT NULL DEFAULT ''")

def esc(v):
    return html.escape(str(v),quote=True)

def fmt(v,casas=2):
    return '—' if v is None else f'{v:,.{casas}f}'.translate(str.maketrans(',.','.,'))

def numero(s, casas):
    s=s.strip() or '0'
    if not re.fullmatch(r'(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?',s):
        raise ValueError('Use vírgula para os decimais e ponto apenas para milhares.')
    n=Decimal(s.replace('.','').replace(',','.')) * 10**casas
    if n != n.to_integral_value() or n>10**13:
        raise ValueError(f'Confira a quantidade de casas decimais (máximo {casas}) e o valor informado.')
    return int(n)

def placa(s):
    p=re.sub(r'[-\s]','',s).upper()
    if not re.fullmatch(r'[A-Z]{3}\d[A-Z0-9]\d{2}',p):
        raise ValueError('Confira os sete caracteres da placa.')
    return p

def versao_nota(nota):
    import hashlib
    return hashlib.sha256(repr(tuple(nota)).encode()).hexdigest()

def campos_nota(nota):
    dados = dict(nota)
    dados['versao'] = versao_nota(nota)
    for campo, casas in [('km',2), ('diesel',3), ('valor',2), ('arla',3), ('valorarla',2)]:
        dados[campo] = fmt(Decimal(nota[campo]).scaleb(-casas), casas)
    return dados

def gravar(f, nota_id=None):
    p=placa(f['placa'])
    d=date.fromisoformat(f['data']).isoformat()
    if not f['km'].strip(): raise ValueError('Informe o hodômetro.')
    km=numero(f['km'],2)
    diesel,valor,arla,va=[numero(f[k],c) for k,c in [('diesel',3),('valor',2),('arla',3),('valorarla',2)]]
    if not (diesel or arla) or bool(diesel)!=bool(valor) or bool(arla)!=bool(va):
        raise ValueError('Informe litros e valor total para cada produto abastecido.')
    with banco() as con:
        con.execute('BEGIN IMMEDIATE')
        if nota_id is not None:
            nota = con.execute('SELECT * FROM notas WHERE id=?', (nota_id,)).fetchone()
            if nota is None:
                raise ValueError('Abastecimento não encontrado.')
            if versao_nota(nota) != f.get('versao'):
                raise ValueError('A nota mudou desde que foi aberta. Volte ao histórico e abra a edição novamente.')
        elif con.execute('SELECT 1 FROM notas WHERE chave=?',(f['chave'],)).fetchone():
            return
        valores = {}
        modelo = f['modelo'].strip()
        for tipo in TIPOS:
            nome = p if tipo == 'placa' else ' '.join(f[tipo].split())
            existente = con.execute('SELECT nome,detalhe FROM cadastros WHERE tipo=? AND chave=?',
                                     (tipo,nome.casefold())).fetchone()
            if existente:
                nome = existente['nome']
                if tipo == 'placa' and not modelo:
                    modelo = existente['detalhe']
            elif f.get('salvar_'+tipo):
                if not nome:
                    raise ValueError('Preencha o campo antes de marcar a opção de cadastro.')
                con.execute('INSERT INTO cadastros(tipo,nome,chave,detalhe) VALUES (?,?,?,?)',
                            (tipo,nome,nome.casefold(),modelo if tipo=='placa' else ''))
            valores[tipo] = nome
        valores_nota = (d,p,modelo,valores['motorista'],valores['posto'],km,diesel,valor,arla,va,
                        f['observacao'].strip(),int(bool(diesel and f.get('parcial'))))
        if nota_id is None:
            con.execute("""INSERT INTO notas(data,placa,modelo,motorista,posto,km,diesel,valor,arla,valorarla,observacao,parcial,chave)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)""", (*valores_nota, f['chave']))
        else:
            con.execute("""UPDATE notas SET data=?,placa=?,modelo=?,motorista=?,posto=?,km=?,diesel=?,
                valor=?,arla=?,valorarla=?,observacao=?,parcial=? WHERE id=?""", (*valores_nota, nota_id))


def consultar(f, por_placa=False):
    ordem = "placa, data, km, id" if por_placa else "data DESC, km DESC, id DESC"
    where=[]; args=[]
    for key,col in [('placa','placa'),('modelo','modelo')]:
        if f.get(key):
            where.append(col+' = ? COLLATE NOCASE'); args.append(f[key])
    for key,op in [('de','>='),('ate','<=')]:
        if f.get(key):
            where.append('data '+op+' ?'); args.append(date.fromisoformat(f[key]).isoformat())
    if f.get('de') and f.get('ate') and f['de']>f['ate']: raise ValueError('Confira a ordem das datas.')
    with banco() as con:
        return con.execute("""WITH acumulados AS (
          SELECT id, placa, data, km, parcial, SUM(diesel) OVER (
            PARTITION BY placa ORDER BY data,km,id ROWS UNBOUNDED PRECEDING
          ) AS total FROM notas WHERE diesel>0
        ), leituras AS (
          SELECT id, km-LAG(km) OVER ordem AS delta, total-LAG(total) OVER ordem AS litros_intervalo
          FROM acumulados WHERE parcial=0 WINDOW ordem AS (PARTITION BY placa ORDER BY data,km,id)
        ), anteriores AS (
          SELECT id, LAG(delta*10.0/litros_intervalo) OVER (PARTITION BY n.placa ORDER BY n.data,n.km,id) AS media_anterior
          FROM leituras JOIN notas n USING(id) WHERE delta>0
        ) SELECT notas.*, CASE WHEN delta>0 THEN delta END AS distancia, litros_intervalo, media_anterior
          FROM notas LEFT JOIN leituras USING(id) LEFT JOIN anteriores USING(id) """
        + (' WHERE '+' AND '.join(where) if where else '')+' ORDER BY '+ordem,args).fetchall()

def opcoes(col):
    with banco() as con:
        return [r[0] for r in con.execute(f"SELECT DISTINCT {col} FROM notas WHERE {col}<>'' ORDER BY 1")]
TIPOS = {'placa':('Veículos','Placa','Modelo'), 'motorista':('Motoristas','Nome do motorista','Telefone'),
         'posto':('Postos','Nome do posto','Cidade / UF')}

def registros(tipo):
    with banco() as con:
        return con.execute('SELECT * FROM cadastros WHERE tipo=? ORDER BY chave',(tipo,)).fetchall()

EXTRAS = {
    'placa': [('marca','Marca'), ('potencia','Potência (cv)'), ('tracao','Configuração de tração')],
    'motorista': [('cidade_moradia','Cidade de moradia')],
    'posto': [('telefone_posto','Telefone do posto')],
}
TRACOES = ('4x2','4x4','6x2','6x4','6x6','8x2','8x4','8x6','8x8')

def lista(col):
    if col == 'placa':
        with banco() as con:
            veiculos = con.execute("""SELECT v.nome, v.detalhe AS modelo, COALESCE(m.nome,'') AS habitual
                FROM cadastros v LEFT JOIN cadastros m ON m.id=v.motorista_id AND m.tipo='motorista'
                WHERE v.tipo='placa' ORDER BY v.chave""")
            return ''.join(f'<option value="{esc(r["nome"][:3])}-{esc(r["nome"][3:])}" data-modelo="{esc(r["modelo"])}" data-motorista="{esc(r["habitual"])}"></option>' for r in veiculos)
    nomes = (r['nome'] for r in registros(col)) if col in TIPOS else opcoes(col)
    return ''.join(f'<option value="{esc(v)}"></option>' for v in nomes)

def salvar_cadastro(tipo, f):
    nome = placa(f['nome']) if tipo=='placa' else ' '.join(f['nome'].split())
    if not nome:
        raise ValueError('Informe '+TIPOS[tipo][1].lower()+'.')
    dados = dict(nome=nome,chave=nome.casefold(),detalhe=f.get('detalhe','').strip(),observacao=f.get('observacao','').strip())
    for coluna, _ in EXTRAS[tipo]:
        if coluna in f:
            dados[coluna] = f[coluna].strip()
    if dados.get('potencia') and not re.fullmatch(r'[1-9][0-9]{0,4}',dados['potencia']):
        raise ValueError('Informe a potência em cv como número inteiro positivo.')
    with banco() as con:
        try:
            if tipo == 'placa' and 'motorista_id' in f:
                motorista_id = f['motorista_id'] or None
                if motorista_id and not con.execute("SELECT 1 FROM cadastros WHERE tipo='motorista' AND id=?", (motorista_id,)).fetchone():
                    raise ValueError('Selecione um motorista cadastrado.')
                dados['motorista_id'] = motorista_id
            if f.get('id'):
                atribuicoes = ','.join(k+'=?' for k in dados)
                cur=con.execute(f'UPDATE cadastros SET {atribuicoes} WHERE tipo=? AND id=?',
                                (*dados.values(),tipo,f['id']))
                if not cur.rowcount: raise ValueError('Cadastro não encontrado.')
            else:
                dados['tipo']=tipo
                con.execute(f"INSERT INTO cadastros({','.join(dados)}) VALUES({','.join('?' for _ in dados)})",tuple(dados.values()))
        except sqlite3.IntegrityError:
            raise ValueError('Já existe um cadastro com essa identificação.')

def tela_cadastros(tipo, f, erro=''):
    titulo,ident,detalhe=TIPOS[tipo]
    tabs=''.join(f'<a class="aba" aria-current="{("page" if k==tipo else "false")}" href="/cadastros/{k}">{v[0]}</a>' for k,v in TIPOS.items())
    rows=registros(tipo)
    if f.get('id') and 'nome' not in f:
        f=next((dict(r) for r in rows if str(r['id'])==f['id']),{})
    url='/cadastros/'+tipo
    bloco=f'<h1>Cadastros</h1><nav class="abas">{tabs}</nav><h2>{titulo}</h2>'
    bloco+=f'<form class="nota" action="{url}" method="post"><h3>{"Editar cadastro" if f.get("id") else "Novo cadastro"}</h3>'
    if erro: bloco+=f'<p role="alert">{esc(erro)}</p>'
    bloco+=f'<input type="hidden" name="token" value="{TOKEN}"><input type="hidden" name="id" value="{esc(f.get("id",""))}"><div class="grade">'
    campos=[('nome',ident),('detalhe',detalhe),*EXTRAS[tipo],('observacao','Observação')]
    atributos={'nome':'required','potencia':'type="number" min="1" max="99999" step="1"',
               'tracao':'list="tracoes" placeholder="Ex.: 6x2"','telefone_posto':'type="tel"'}
    for k,label in campos:
        if tipo == 'placa' and k == 'nome':
            atributos[k] = 'required data-placa placeholder="ABC-1234"'
        bloco+=f'<label>{label}<input name="{k}" value="{esc(f.get(k,""))}" {atributos.get(k,"")}></label>'
    if tipo=='placa':
        bloco+='<datalist id="tracoes">'+''.join(f'<option value="{v}"></option>' for v in TRACOES)+'</datalist>'
        motoristas = {r['id']:r['nome'] for r in registros('motorista')}
        bloco+='<label>Motorista habitual<select name="motorista_id"><option value="">Sem motorista habitual</option>'
        for codigo,nome in motoristas.items():
            selecionado = ' selected' if str(codigo)==str(f.get('motorista_id')) else ''
            bloco+=f'<option value="{codigo}"{selecionado}>{esc(nome)}</option>'
        bloco+='</select></label>'
    bloco+='</div><button>Salvar cadastro</button>'
    if f.get('id'): bloco+=f' <a href="{url}">Cancelar edição</a>'
    bloco+='</form><h3>Registros ('+str(len(rows))+')</h3>'
    if rows:
        if tipo == 'placa':
            campos.append(('habitual','Motorista habitual'))
            rows=[dict(r,habitual=motoristas.get(r['motorista_id'],'')) for r in rows]
        bloco+='<div class="tabela"><table><tr>'+''.join(f'<th>{label}</th>' for _,label in campos)+'<th></th></tr>'
        for r in rows:
            bloco+='<tr>'+''.join('<td>'+esc(r[k] or '—')+'</td>' for k,_ in campos)+f'<td><a href="{url}?id={r["id"]}">Editar</a></td></tr>'
        bloco+='</table></div>'
    else: bloco+='<article>Nenhum cadastro ainda.</article>'
    return pagina(titulo,bloco)
MASCARA_PLACA = """<script>
for (const campo of document.querySelectorAll('input[data-placa]')) {
    function formatar() {
        const original = campo.value;
        const letrasNumeros = texto => texto.replace(/[^a-z0-9]/gi, '').toUpperCase();
        const codigo = letrasNumeros(original).slice(0, 7);
        const formatada = codigo.length > 3 ? codigo.slice(0, 3) + '-' + codigo.slice(3) : codigo;
        if (original === formatada) return;
        const antes = letrasNumeros(original.slice(0, campo.selectionStart)).length;
        campo.value = formatada;
        const cursor = Math.min(formatada.length, antes + (antes > 3 ? 1 : 0));
        campo.setSelectionRange(cursor, cursor);
    }
    campo.addEventListener('beforeinput', evento => {
        if (evento.inputType === 'deleteContentForward' && campo.selectionStart === 3 &&
            campo.selectionEnd === 3 && campo.value[3] === '-') campo.setSelectionRange(4, 4);
    });
    campo.addEventListener('input', formatar);
    formatar();
}
</script>"""

def pagina(titulo,body):
    if PORT == 8012:
        body = '<p><strong>Demonstração · dados fictícios</strong></p>' + body
    if 'data-placa' in body:
        body += MASCARA_PLACA
    return f'''<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{titulo} · FrotaGestor</title><link rel="stylesheet" href="/estilo.css"><header><div><a href="/"><b>FrotaGestor</b></a><nav class="menu"><a href="/">Abastecimentos</a><a href="/relatorios">Relatórios</a><a href="/cadastros">Cadastros</a></nav></div></header><main>{body}</main></html>'''
def formulario(f=None,erro='',nota_id=None):
    titulo = 'Editar abastecimento' if nota_id is not None else 'Novo abastecimento'
    acao = f'/editar/{nota_id}' if nota_id is not None else '/salvar'
    botao = 'Salvar alterações' if nota_id is not None else 'Salvar abastecimento'
    f={} if f is None else f
    def campo(k,t,attr=''):
        entrada=f'<label>{t}<input name="{k}" value="{esc(f.get(k,date.today().isoformat() if k=="data" else ""))}" {attr}></label>'
        if k in TIPOS:
            return f'<div>{entrada}<label class="marcar"><input type="checkbox" name="salvar_{k}" {"checked" if f.get("salvar_"+k) else ""}>Salvar no cadastro de {TIPOS[k][0].lower()}</label></div>'
        return entrada
    return pagina(titulo,f'''<a href="/">← Voltar ao relatório</a><h1>{titulo}</h1>
    <form method="post" action="{acao}" class="nota"><input type="hidden" name="token" value="{TOKEN}"><input type="hidden" name="chave" value="{esc(f.get('chave',secrets.token_hex(16)))}">
    <input type="hidden" name="versao" value="{esc(f.get('versao',''))}">
    {('<p role="alert">'+esc(erro)+'</p>') if erro else ''}<h2>Veículo e nota</h2><div class="grade">'''
    +campo('data','Data','type=date required')+campo('placa','Placa','list=placas required data-placa placeholder="ABC-1234"')+campo('km','Hodômetro (km)','required inputmode=decimal')
    +campo('modelo','Modelo','list=modelos')+campo('motorista','Motorista','list=motoristas')+campo('posto','Posto','list=postos')+'</div>'
    +''.join(f'<datalist id="{k}s">{lista(k)}</datalist>' for k in ['placa','modelo','motorista','posto'])
    +f'<input type="hidden" name="motorista_manual" value="{int(f.get("motorista_manual")=="1")}">'
    +r'''<script>
    const formNota = document.querySelector('form.nota');
    const placaNota = formNota.elements.placa;
    const motoristaNota = formNota.elements.motorista;
    const veiculos = new Map(Array.from(placaNota.list.options, o => [o.value.replace('-', ''), o.dataset]));
    const manual = formNota.elements.motorista_manual;
    motoristaNota.addEventListener('input', () => { manual.value = '1'; });
    placaNota.addEventListener('input', () => {
        const chave = placaNota.value.toUpperCase().replace(/[-\s]/g, '');
        const veiculo = veiculos.get(chave);
        formNota.elements.modelo.value = veiculo?.modelo || '';
        if (manual.value !== '1') motoristaNota.value = veiculo?.motorista || '';
    });
    </script>'''
    +'<h2>Produtos da nota</h2><div class="grade produtos">'+''.join(campo(k,t,'inputmode=decimal') for k,t in [('diesel','Diesel (L)'),('valor','Total diesel (R$)'),('arla','ARLA (L)'),('valorarla','Total ARLA (R$)')])+f'</div><label class="marcar"><input type="checkbox" name="parcial" {"checked" if f.get("parcial") else ""}>Tanque de diesel parcial (não completei)</label><label>Observação<textarea name="observacao">'+esc(f.get('observacao',''))+f'</textarea></label><button>{botao}</button></form>')

COLUNAS=['Data','Placa','Modelo','Motorista','Posto','Hodômetro (km)','Distância (km)','Diesel (L)','Diesel (R$)','Diesel (R$/L)','Média (km/L)','ARLA (L)','ARLA (R$)','Observação','Tanque de diesel']
def linha(r):
    km=r['distancia']
    return [date.fromisoformat(r['data']).strftime('%d/%m/%Y'),r['placa'],r['modelo'],r['motorista'],r['posto'],fmt(r['km']/100),fmt(km/100 if km else None),fmt(r['diesel']/1000,3),fmt(r['valor']/100),fmt(r['valor']*10/r['diesel'] if r['diesel'] else None,3),fmt(km*10/r['litros_intervalo'] if km else None,3),fmt(r['arla']/1000,3),fmt(r['valorarla']/100),r['observacao'],('Parcial' if r['parcial'] else 'Completo') if r['diesel'] else '-']
def media_diesel(rows):
    validos=[r for r in rows if r['distancia']]
    litros=sum(r['litros_intervalo'] for r in validos)
    return sum(r['distancia'] for r in validos)*10/litros if litros else None

def relatorio(f,rows):
    def select(k,t):
        return '<label>'+t+'<select name="'+k+'"><option value="">Todos</option>'+''.join(f'<option {"selected" if f.get(k)==v else ""}>{esc(v)}</option>' for v in opcoes(k))+'</select></label>'
    media = media_diesel(rows)
    itens=[('Gasto no período','R$ '+fmt(sum(r['valor']+r['valorarla'] for r in rows)/100)),('Diesel abastecido',fmt(sum(r['diesel'] for r in rows)/1000,3)+' L'),('Média de diesel',fmt(media,3)+' km/L' if media else 'Sem referência'),('ARLA abastecido',fmt(sum(r['arla'] for r in rows)/1000,3)+' L')]
    view='<div class="top"><h1>Abastecimentos</h1><nav><a class="botao" href="/relatorios">Relatórios e impressão A4</a><a class="botao" href="/novo">+ Novo abastecimento</a></nav></div><form class="filtros">'+select('placa','Placa')+select('modelo','Modelo')
    for k,t in [('de','De'),('ate','Até')]: view+=f'<label>{t}<input type="date" name="{k}" value="{esc(f.get(k,""))}"></label>'
    view+='<button>Consultar</button><a href="/">Limpar</a></form><div class="cards">'+''.join(f'<section><small>{t}</small><strong>{v}</strong></section>' for t,v in itens)+'</div>'
    view+=f'<div class="top"><h2>Histórico <small>({len(rows)})</small></h2><nav><a href="/csv?{esc(urlencode(f))}">Baixar CSV</a> <button onclick="window.print()">Imprimir</button></nav></div>'
    if not rows: return pagina('Abastecimentos',view+'<article>Nenhum abastecimento encontrado. Cadastre uma nota ou ajuste os filtros.</article>')
    idx=[0,1,3,5,6,7,8,14,10,12]
    view+='<div class="tabela"><table><thead><tr>'+''.join('<th>'+COLUNAS[i]+'</th>' for i in idx)+'<th>Detalhes</th></tr></thead><tbody>'
    for r in rows:
        valores=linha(r)
        view+='<tr>'+''.join('<td>'+esc(valores[i] or '—')+'</td>' for i in idx)+'<td><details><summary>Ver nota</summary>'+''.join(f'<p>{COLUNAS[i]}: {esc(valores[i] or "—")}</p>' for i in [2,4,9,11,13])+f'</details><a href="/editar/{r["id"]}">Editar</a></td></tr>'
    return pagina('Abastecimentos',view+'</tbody></table></div><p class="rodape">A média usa a distância entre dois tanques completos e todo o diesel abastecido nesse intervalo. O período considera a data em que o tanque foi completado. O resumo usa a distância total dividida pelos litros dos intervalos válidos.</p>')
INDICADORES = {'consumo': ('Consumo médio', 'km/L', 3),
               'preco': ('Preço médio do diesel', 'R$/L', 2),
               'gasto': ('Gasto com diesel e ARLA', 'R$', 2)}

def preco_diesel(notas):
    litros = sum(r['diesel'] for r in notas)
    return sum(r['valor'] for r in notas)*10/litros if litros else None

def grafico_periodo(rows, inicio, fim, indicador, papel=False):
    titulo, unidade, casas = INDICADORES[indicador]
    mensal = (fim-inicio).days > 62
    grupos = {}
    datas = {r['data'][:7] if mensal else r['data'] for r in rows}
    faixa = ((fim-inicio).days//8 + 1) if papel and len(datas)>8 else 0
    for r in rows:
        chave = r['data'][:7] if mensal else r['data']
        if faixa:
            dias = (date.fromisoformat(r['data'])-inicio).days
            chave = date.fromordinal(inicio.toordinal()+dias//faixa*faixa).isoformat()
        grupos.setdefault(chave, []).append(r)
    pontos = []
    for chave, notas in sorted(grupos.items()):
        dia = date.fromisoformat(chave+'-01' if mensal and not faixa else chave)
        if indicador == 'consumo':
            valor = media_diesel(notas)
        elif indicador == 'preco':
            valor = preco_diesel(notas)
        else:
            valor = sum(r['valor']+r['valorarla'] for r in notas)/100
        if valor is not None:
            pontos.append((dia, valor))
    if not pontos:
        return f'<article><h2>{titulo}</h2><p>Sem dados suficientes para este indicador no período.</p></article>'
    def posicao(d):
        return d.year*12+d.month if mensal and not faixa else d.toordinal()
    primeiro, ultimo = posicao(inicio), posicao(fim)
    posicoes = []
    deslocamento = 0
    for dia, _ in pontos:
        x = 433 if primeiro == ultimo else 102 + 660*(posicao(dia)-primeiro)/(ultimo-primeiro)
        x += deslocamento
        if posicoes and x < posicoes[-1] + 56:
            ajuste = posicoes[-1] + 56 - x
            deslocamento += ajuste
            x += ajuste
        posicoes.append(x)
    largura_svg = 800 + int(deslocamento + 1)
    teto = max(v for _,v in pontos) or 1
    escala = f'faixa de {faixa} dias (data inicial)' if faixa else ('mês' if mensal else 'dia')
    partes = [f'<article><h2>{titulo} ({unidade})</h2><p>Valores por {escala} com referência disponível.</p>',
              f'<div style="overflow-x:auto"><svg viewBox="0 0 {largura_svg} 220" style="display:block;width:100%;min-width:{largura_svg}px;height:auto" role="img" aria-label="{titulo} por período">']
    for fracao in (0, 0.5, 1):
        y = 173 - fracao*143
        partes.append(f'<path d="M 96 {y} H {largura_svg-30}" stroke="#d6dfdc"/><text x="88" y="{y+5}" text-anchor="end" fill="#314c48" font-size="13">{fmt(teto*fracao,casas)}</text>')
    coordenadas = []
    marcas = []
    tabela = []
    largura = min(32, 550/max(1,ultimo-primeiro+1))
    for (dia, valor), x in zip(pontos, posicoes):
        y = 173-143*valor/teto
        data_txt = dia.strftime('%m/%Y' if mensal and not faixa else '%d/%m/%Y')
        descricao = f'<title>{data_txt} · {fmt(valor,casas)} {unidade}</title>'
        if indicador == 'gasto':
            marcas.append(f'<rect x="{x-largura/2:.2f}" y="{y:.2f}" width="{largura:.2f}" height="{173-y:.2f}" fill="#19786a">{descricao}</rect>')
        else:
            coordenadas.append(f'{x:.2f},{y:.2f}')
            marcas.append(f'<circle cx="{x:.2f}" cy="{y:.2f}" r="4" fill="#075c50">{descricao}</circle>')
        rotulo = dia.strftime('%m/%Y' if mensal and not faixa else '%d/%m')
        marcas.append(f'<path d="M {x:.2f} 174 v 6" stroke="#718680"/>'
                      f'<text x="{x:.2f}" y="199" text-anchor="middle" font-size="13" fill="#314c48">{rotulo}</text>')
        marcas.append(f'<text x="{x:.2f}" y="{y-9:.2f}" text-anchor="middle" font-size="13" font-weight="bold" fill="#173e3b">{fmt(valor,casas)}</text>')
        tabela.append(f'<tr><td>{data_txt}</td><td>{fmt(valor,casas)}</td></tr>')
    if coordenadas:
        partes.append(f'<polyline points="{" ".join(coordenadas)}" fill="none" stroke="#218778" stroke-width="3"/>')
    partes.extend(marcas)
    partes.append('</svg></div>')
    partes.append(f'<details><summary>Ver valores do gráfico</summary><table><thead><tr><th>Data</th><th>{titulo} ({unidade})</th></tr></thead><tbody>')
    return ''.join(partes+tabela)+ '</tbody></table></details></article>'

def tabela_lancamentos(rows):
    itens = ['<table><thead><tr><th>Data</th><th>Placa</th><th>Tanque</th><th>Diesel (L)</th><th>ARLA (L)</th><th>Total (R$)</th><th>km/L</th><th>Variação¹</th></tr></thead><tbody>']
    for r in rows:
        media = media_diesel([r])
        anterior = r['media_anterior']
        variacao = 100*(media/anterior-1) if media and anterior else None
        diferenca = ('+' if variacao>0 else '')+fmt(variacao,1)+'%' if variacao is not None else '—'
        tanque = ('Parcial' if r['parcial'] else 'Cheio') if r['diesel'] else 'Só ARLA'
        placa = r['placa']
        valores = (date.fromisoformat(r['data']).strftime('%d/%m/%y'), placa[:3]+'-'+placa[3:], tanque,
                   fmt(r['diesel']/1000,3), fmt(r['arla']/1000,3), fmt((r['valor']+r['valorarla'])/100), fmt(media,3), diferenca)
        itens.append('<tr>'+''.join(f'<td>{esc(x)}</td>' for x in valores)+'</tr>')
    return ''.join(itens)+'</tbody></table>'

NOTA_VARIACAO = '¹ Variação em relação à média anterior válida da mesma placa. O traço indica que faltou referência para calcular.'

def documento_a4(rows, filtros, indicador, inicio, fim):
    css = """
    @page{size:A4 portrait;margin:12mm}*{box-sizing:border-box}
    body{background:#eceeea;margin:0;color:#1b302f;font:10pt Arial,sans-serif}
    main{width:186mm;margin:5mm auto;background:white;padding:8mm}
    nav{text-align:center;padding:12px}button{padding:9px 16px;cursor:pointer}
    header{border-top:3px solid #18564d;padding-top:8px}header small,footer{color:#47625e}
    h1{font:bold 23pt Georgia,serif;margin:5px 0}h2{font-size:12pt;margin:10px 0 4px}
    p{margin:4px 0}.numeros{padding:8px 0;border-block:1px solid #879d98;margin:9px 0;line-height:1.6}
    article div{overflow:visible!important}svg{min-width:0!important;width:100%!important;height:42mm!important}
    details{display:none}table{border-collapse:collapse;width:100%;font-size:8pt;font-variant-numeric:tabular-nums}
    td,th{text-align:right;padding:3px 2px;border-bottom:1px solid #d2dcd8;white-space:nowrap}td:nth-child(-n+3),th:nth-child(-n+3){text-align:left}
    th{color:#18564d}footer{margin-top:8px;font-size:8pt}.filtro{overflow-wrap:anywhere}
    @media print{body{background:white}nav{display:none}main{width:auto;margin:0;padding:0;break-after:page}main:last-child{break-after:auto}}
    """
    paginas = [rows[i:i+20] for i in range(0,len(rows),20)] or [[]]
    saida = [f'<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Relatório de abastecimentos</title><style>{css}</style><body><nav><button onclick="window.print()">Imprimir ou salvar PDF</button></nav>']
    diesel = sum(r['valor'] for r in rows)
    arla = sum(r['valorarla'] for r in rows)
    filtro = ' · '.join(f'{k.capitalize()} {v}' for k,v in filtros.items() if k in ('placa','modelo')) or 'Toda a frota'
    for num, notas in enumerate(paginas,1):
        origem = 'FrotaGestor · Demonstração' if PORT==8012 else 'FrotaGestor'
        saida.append(f'<main><header><small>{origem} · Folha {num} de {len(paginas)}</small><h1>Relatório de abastecimentos</h1><p class="filtro">{inicio:%d/%m/%Y} a {fim:%d/%m/%Y} · {esc(filtro)}</p></header>')
        if num==1:
            saida.append(f'<div class="numeros"><b>Gasto total R$ {fmt((diesel+arla)/100)}</b> · Diesel R$ {fmt(diesel/100)} · ARLA R$ {fmt(arla/100)}<br>Média {fmt(media_diesel(rows),3)} km/L · Diesel {fmt(preco_diesel(rows))} R$/L · {len(rows)} lançamentos</div>')
            saida.append(grafico_periodo(rows,inicio,fim,indicador,papel=True))
        saida.append('<h2>Abastecimentos do período</h2>')
        saida.append(tabela_lancamentos(notas) if notas else '<p>Nenhum lançamento neste período.</p>')
        saida.append(f'<footer>{NOTA_VARIACAO}</footer></main>')
    return ''.join(saida)+'</body></html>'

def painel_mensal(f, mes, papel=False):
    from calendar import monthrange
    if not re.fullmatch(r'[0-9]{4}-[0-9]{2}', mes):
        raise ValueError('Mês inválido')
    inicio = date.fromisoformat(mes + '-01')
    ultimo = monthrange(inicio.year, inicio.month)[1]
    filtros = {k: f[k] for k in ('placa', 'modelo') if f.get(k)}
    fim = date.fromisoformat(f.get('ate') or inicio.replace(day=ultimo).isoformat())
    inicio = date.fromisoformat(f.get('de') or inicio.isoformat())
    filtros.update(de=inicio.isoformat(), ate=fim.isoformat())
    rows = consultar(filtros, por_placa=not filtros.get("placa"))
    indicador = f.get('indicador', 'consumo')
    if indicador not in INDICADORES:
        indicador = 'consumo'
    if papel:
        return documento_a4(rows, filtros, indicador, inicio, fim)
    html = ['<div class="top"><h1>Relatório de abastecimentos</h1></div>',
            f'<form class="filtros"><label>De<input type="date" name="de" value="{inicio}" required></label>'
            f'<label>Até<input type="date" name="ate" value="{fim}" required></label>']
    for k in ('placa', 'modelo'):
        html.append(f'<label>{k.capitalize()}<select name="{k}"><option value="">Todos</option>')
        for v in opcoes(k):
            selected = ' selected' if filtros.get(k) == v else ''
            html.append(f'<option{selected}>{esc(v)}</option>')
        html.append('</select></label>')
    html.append('<label>Indicador do gráfico<select name="indicador">')
    for chave, (nome, unidade, _) in INDICADORES.items():
        selected = ' selected' if indicador==chave else ''
        html.append(f'<option value="{chave}"{selected}>{nome} ({unidade})</option>')
    html.append('</select></label><button>Consultar</button><button type="submit" formaction="/relatorios/a4">Gerar relatório A4</button><a href="/relatorios">Mês atual</a></form>')
    if not rows:
        html.append('<article>Nenhum abastecimento neste período para os filtros selecionados.</article>')
        return pagina('Relatório de abastecimentos', ''.join(html))

    diesel = sum(r['valor'] for r in rows)
    arla = sum(r['valorarla'] for r in rows)
    media = media_diesel(rows)
    por_placa = {}
    for r in rows:
        por_placa.setdefault(r['placa'], []).append(r)
    preco = preco_diesel(rows)
    cards = [('Preço médio do diesel', fmt(preco)+' R$/L' if preco is not None else 'Sem diesel'), ('Gasto total', 'R$ '+fmt((diesel+arla)/100)),
             ('Diesel', 'R$ '+fmt(diesel/100)), ('ARLA', 'R$ '+fmt(arla/100)),
             ('Média da frota filtrada', fmt(media,3)+' km/L' if media else 'Sem referência')]
    html.append('<div class="cards">')
    for titulo, valor in cards:
        html.append(f'<section><small>{titulo}</small><strong>{valor}</strong></section>')
    html.append('</div>')
    html.append(grafico_periodo(rows, inicio, fim, indicador))
    html.append('<h2>Consumo por placa</h2>'
                '<p>A média considera os intervalos encerrados no período entre dois tanques completos, incluindo os litros dos parciais. Clique na placa para conferir as notas.</p>')
    grupos = [(placa, notas, media_diesel(notas)) for placa, notas in por_placa.items()]
    grupos.sort(key=lambda item: (-(item[2] or 0), item[0]))
    maior = max((m or 0 for _, _, m in grupos), default=0)
    html.append('<div class="tabela"><table><thead><tr><th>Placa</th><th>Modelo</th><th>Média (km/L)</th>'
                '<th>Km com referência</th><th>Diesel (L)</th><th>ARLA (L)</th><th>Gasto total</th><th>Notas</th></tr></thead><tbody>')
    for placa, notas, media in grupos:
        link = esc(urlencode(dict(filtros, placa=placa)))
        distancia = sum(r['distancia'] or 0 for r in notas)
        consumo = (f'{fmt(media,3)} <meter aria-hidden="true" min="0" max="{maior}" value="{media}"></meter>'
                   if media else 'Sem referência')
        modelos = ', '.join(sorted({r['modelo'] for r in notas if r['modelo']}))
        html.append(f'<tr><td><a href="/?{link}">{esc(placa[:3])}-{esc(placa[3:])}</a></td>'
                    f'<td>{esc(modelos) or "–"}</td><td>{consumo}</td><td>{fmt(distancia/100) if distancia else "–"}</td>'
                    f'<td>{fmt(sum(r["diesel"] for r in notas)/1000,3)}</td>'
                    f'<td>{fmt(sum(r["arla"] for r in notas)/1000,3)}</td>'
                    f'<td>R$ {fmt(sum(r["valor"]+r["valorarla"] for r in notas)/100)}</td><td>{len(notas)}</td></tr>')
    html.append(f'</tbody></table></div><p><a href="/csv?origem=relatorio&amp;{esc(urlencode(filtros))}">Baixar notas do período (CSV)</a></p>')
    html.append('<h2>Abastecimentos um por um</h2><div class="tabela">'+tabela_lancamentos(rows)+'</div><p>'+NOTA_VARIACAO+'</p>')
    return pagina('Relatório de abastecimentos', ''.join(html))

class Handler(BaseHTTPRequestHandler):
    def enviar(self,body,tipo='text/html; charset=utf-8',status=200):
        data=body.encode()
        self.send_response(status); self.send_header('Content-Type',tipo); self.send_header('Content-Length',str(len(data))); self.send_header('Cache-Control','no-store')
        if tipo.startswith('text/csv'): self.send_header('Content-Disposition','attachment; filename="abastecimentos.csv"')
        self.end_headers(); self.wfile.write(data)
    def permitido(self):
        if self.headers.get('Host') not in {f'localhost:{PORT}',f'127.0.0.1:{PORT}'}:
            self.send_error(403); return False
        return True
    def do_GET(self):
        if not self.permitido(): return
        u=urlsplit(self.path)
        tipo=u.path.removeprefix('/cadastros/')
        if u.path=='/cadastros' or (u.path.startswith('/cadastros/') and tipo in TIPOS):
            return self.enviar(tela_cadastros(tipo if tipo in TIPOS else 'placa',{'id':parse_qs(u.query).get('id',[''])[0]}))
        f={k:v[-1] for k,v in parse_qs(u.query).items() if k in ('placa','modelo','de','ate')}
        if u.path in ('/relatorios','/relatorios/a4'):
            f['indicador'] = parse_qs(u.query).get('indicador', ['consumo'])[-1]
            mes = parse_qs(u.query).get('mes', [date.today().isoformat()[:7]])[-1]
            try: return self.enviar(painel_mensal(f, mes, papel=u.path.endswith('/a4')))
            except ValueError: return self.enviar(pagina('Relatório de abastecimentos','<p>Confira as datas e a ordem do período. <a href="/relatorios">Abrir mês atual</a></p>'),status=422)
        editar = re.fullmatch(r'/editar/([1-9][0-9]{0,17})', u.path)
        if editar:
            with banco() as con:
                nota = con.execute('SELECT * FROM notas WHERE id=?', (editar[1],)).fetchone()
            if nota is None: return self.send_error(404)
            return self.enviar(formulario(campos_nota(nota), nota_id=int(editar[1])))
        if u.path=='/novo': return self.enviar(formulario())
        if u.path=='/estilo.css': return self.enviar((ROOT/'estilo.css').read_text(encoding='utf8'),'text/css')
        if u.path not in ('/','/csv'): return self.send_error(404)
        exportar_por_placa = u.path == "/csv" and parse_qs(u.query).get("origem") == ["relatorio"] and not f.get("placa")
        try: rows=consultar(f, por_placa=exportar_por_placa)
        except ValueError: return self.enviar(pagina('Consulta','<p>Confira o período informado.</p><a href="/">Voltar</a>'),status=422)
        if u.path=='/': return self.enviar(relatorio(f,rows))
        out=io.StringIO(); writer=csv.writer(out,delimiter=';'); writer.writerow(COLUNAS)
        for r in rows: writer.writerow(["'"+v if v.lstrip().startswith(('=','+','-','@')) else v for v in linha(r)])
        self.enviar('\ufeff'+out.getvalue(),'text/csv; charset=utf-8')
    def do_POST(self):
        if not self.permitido(): return
        tipo=self.path.removeprefix('/cadastros/')
        cadastro=self.path.startswith('/cadastros/') and tipo in TIPOS
        editar = re.fullmatch(r'/editar/([1-9][0-9]{0,17})', self.path)
        nota_id = int(editar[1]) if editar else None
        if self.path!='/salvar' and not cadastro and not editar: return self.send_error(404)
        n=int(self.headers.get('Content-Length','0'))
        if not 0<n<=32000: return self.send_error(413)
        f={k:v[-1] for k,v in parse_qs(self.rfile.read(n).decode(),keep_blank_values=True).items()}
        if not secrets.compare_digest(f.get('token',''),TOKEN): return self.send_error(403)
        try:
            if cadastro: salvar_cadastro(tipo,f)
            else: gravar(f, nota_id)
        except (KeyError,ValueError,InvalidOperation) as e:
            return self.enviar(tela_cadastros(tipo,f,str(e)) if cadastro else formulario(f,str(e),nota_id),status=422)
        self.send_response(303); self.send_header('Location',self.path if cadastro else '/'); self.end_headers()
def carregar_demonstracao():
    veiculos = [('DEM1A01','Scania R 500',320000), ('DEM2B02','Volvo FH 460',410000),
                ('DEM3C03','Iveco Hi-Way',195000)]
    # Data, distância e litros desde a parada anterior do veículo.
    paradas = [('2026-10-28',0,300), ('2026-11-02',780,300), ('2026-11-06',350,90),
               ('2026-11-09',500,245), ('2026-11-14',900,330), ('2026-11-20',760,310),
               ('2026-11-26',860,320)]
    for indice, (placa_demo, modelo, km) in enumerate(veiculos):
        for i, (dia, distancia, litros) in enumerate(paradas):
            km += distancia
            volume = litros + 10*indice
            preco_centavos = (615,628,649,633,612,604,632)[i] + 3*indice
            arla = 14 + indice if i in (1,3,5) else 0
            gravar(dict(data=dia, placa=placa_demo, modelo=modelo, km=str(km), diesel=str(volume),
                        valor=fmt(Decimal(volume*preco_centavos)/100), arla=str(arla), valorarla=str(arla*3),
                        motorista=f'Motorista Demo {indice+1}', posto=f'Posto Demo {1+i%2}',
                        observacao='Lançamento fictício', parcial=(i==2), chave=f'demo-{placa_demo}-{dia}'))

if __name__=='__main__':
    demonstracao = '--demonstracao' in sys.argv
    if demonstracao:
        DB = ROOT / 'dados' / 'demonstracao.sqlite'
        PORT = 8012
    iniciar()
    if demonstracao:
        carregar_demonstracao()
    with ThreadingHTTPServer(('127.0.0.1',PORT),Handler) as servidor:
        url=f'http://127.0.0.1:{PORT}'
        if demonstracao:
            url += '/relatorios?de=2026-11-01&ate=2026-11-30'
        print('FrotaGestor em '+url,flush=True)
        if os.environ.get('FROTA_SEM_NAVEGADOR')!='1': webbrowser.open(url)
        servidor.serve_forever()
