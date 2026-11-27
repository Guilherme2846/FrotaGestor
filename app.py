import csv
import html
import io
import os
import re
import secrets
import sqlite3
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
        CREATE INDEX IF NOT EXISTS placa_data ON notas(placa,data,km,id);""")

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

def gravar(f):
    p=placa(f['placa'])
    d=date.fromisoformat(f['data']).isoformat()
    if not f['km'].strip(): raise ValueError('Informe o hodômetro.')
    km=numero(f['km'],2)
    diesel,valor,arla,va=[numero(f[k],c) for k,c in [('diesel',3),('valor',2),('arla',3),('valorarla',2)]]
    if not (diesel or arla) or bool(diesel)!=bool(valor) or bool(arla)!=bool(va):
        raise ValueError('Informe litros e valor total para cada produto abastecido.')
    with banco() as con:
        con.execute("""INSERT INTO notas(data,placa,modelo,motorista,posto,km,diesel,valor,arla,valorarla,observacao,chave)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(chave) DO NOTHING""",
        (d,p,f['modelo'].strip(),f['motorista'].strip(),f['posto'].strip(),km,diesel,valor,arla,va,f['observacao'].strip(),f['chave']))

def consultar(f):
    where=[]; args=[]
    for key,col in [('placa','placa'),('modelo','modelo')]:
        if f.get(key):
            where.append(col+' = ? COLLATE NOCASE'); args.append(f[key])
    for key,op in [('de','>='),('ate','<=')]:
        if f.get(key):
            where.append('data '+op+' ?'); args.append(date.fromisoformat(f[key]).isoformat())
    if f.get('de') and f.get('ate') and f['de']>f['ate']: raise ValueError('Confira a ordem das datas.')
    with banco() as con:
        return con.execute("""WITH leituras AS (
          SELECT id, km-LAG(km) OVER(PARTITION BY placa ORDER BY data,km,id) AS delta FROM notas WHERE diesel>0
        ) SELECT notas.*, CASE WHEN delta>0 THEN delta END AS distancia FROM notas LEFT JOIN leituras USING(id) """
        + (' WHERE '+' AND '.join(where) if where else '')+' ORDER BY data DESC,km DESC,id DESC',args).fetchall()

def opcoes(col):
    with banco() as con:
        return [r[0] for r in con.execute(f"SELECT DISTINCT {col} FROM notas WHERE {col}<>'' ORDER BY 1")]
def lista(col):
    return ''.join(f'<option value="{esc(v)}"></option>' for v in opcoes(col))
def pagina(titulo,body):
    return f'''<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{titulo} · FrotaGestor</title><link rel="stylesheet" href="/estilo.css"><header><div><a href="/"><b>FrotaGestor</b></a><span>Controle de abastecimentos</span></div></header><main>{body}</main></html>'''
def formulario(f=None,erro=''):
    f={} if f is None else f
    def campo(k,t,attr=''):
        return f'<label>{t}<input name="{k}" value="{esc(f.get(k,date.today().isoformat() if k=="data" else ""))}" {attr}></label>'
    return pagina('Novo abastecimento',f'''<a href="/">← Voltar ao relatório</a><h1>Novo abastecimento</h1>
    <form method="post" action="/salvar" class="nota"><input type="hidden" name="token" value="{TOKEN}"><input type="hidden" name="chave" value="{esc(f.get('chave',secrets.token_hex(16)))}">
    {('<p role="alert">'+esc(erro)+'</p>') if erro else ''}<h2>Veículo e nota</h2><div class="grade">'''
    +campo('data','Data','type=date required')+campo('placa','Placa','list=placas required maxlength=8')+campo('km','Hodômetro (km)','required inputmode=decimal')
    +campo('modelo','Modelo','list=modelos')+campo('motorista','Motorista','list=motoristas')+campo('posto','Posto','list=postos')+'</div>'
    +''.join(f'<datalist id="{k}s">{lista(k)}</datalist>' for k in ['placa','modelo','motorista','posto'])
    +'<h2>Produtos da nota</h2><div class="grade produtos">'+''.join(campo(k,t,'inputmode=decimal') for k,t in [('diesel','Diesel (L)'),('valor','Total diesel (R$)'),('arla','ARLA (L)'),('valorarla','Total ARLA (R$)')])+'</div><label>Observação<textarea name="observacao">'+esc(f.get('observacao',''))+'</textarea></label><button>Salvar abastecimento</button></form>')

COLUNAS=['Data','Placa','Modelo','Motorista','Posto','Hodômetro (km)','Distância (km)','Diesel (L)','Diesel (R$)','Diesel (R$/L)','Média (km/L)','ARLA (L)','ARLA (R$)','Observação']
def linha(r):
    km=r['distancia']
    return [date.fromisoformat(r['data']).strftime('%d/%m/%Y'),r['placa'],r['modelo'],r['motorista'],r['posto'],fmt(r['km']/100),fmt(km/100 if km else None),fmt(r['diesel']/1000,3),fmt(r['valor']/100),fmt(r['valor']*10/r['diesel'] if r['diesel'] else None,3),fmt(km*10/r['diesel'] if km else None,3),fmt(r['arla']/1000,3),fmt(r['valorarla']/100),r['observacao']]
def relatorio(f,rows):
    def select(k,t):
        return '<label>'+t+'<select name="'+k+'"><option value="">Todos</option>'+''.join(f'<option {"selected" if f.get(k)==v else ""}>{esc(v)}</option>' for v in opcoes(k))+'</select></label>'
    validos=[r for r in rows if r['distancia']]
    litros=sum(r['diesel'] for r in validos)
    media=sum(r['distancia'] for r in validos)*10/litros if litros else None
    itens=[('Gasto no período','R$ '+fmt(sum(r['valor']+r['valorarla'] for r in rows)/100)),('Diesel abastecido',fmt(sum(r['diesel'] for r in rows)/1000,3)+' L'),('Média de diesel',fmt(media,3)+' km/L' if media else 'Sem referência'),('ARLA abastecido',fmt(sum(r['arla'] for r in rows)/1000,3)+' L')]
    view='<div class="top"><h1>Abastecimentos</h1><a class="botao" href="/novo">+ Novo abastecimento</a></div><form class="filtros">'+select('placa','Placa')+select('modelo','Modelo')
    for k,t in [('de','De'),('ate','Até')]: view+=f'<label>{t}<input type="date" name="{k}" value="{esc(f.get(k,""))}"></label>'
    view+='<button>Consultar</button><a href="/">Limpar</a></form><div class="cards">'+''.join(f'<section><small>{t}</small><strong>{v}</strong></section>' for t,v in itens)+'</div>'
    view+=f'<div class="top"><h2>Histórico <small>({len(rows)})</small></h2><nav><a href="/csv?{esc(urlencode(f))}">Baixar CSV</a> <button onclick="window.print()">Imprimir</button></nav></div>'
    if not rows: return pagina('Abastecimentos',view+'<article>Nenhum abastecimento encontrado. Cadastre uma nota ou ajuste os filtros.</article>')
    idx=[0,1,3,5,6,7,8,10,12]
    view+='<div class="tabela"><table><thead><tr>'+''.join('<th>'+COLUNAS[i]+'</th>' for i in idx)+'<th>Detalhes</th></tr></thead><tbody>'
    for r in rows:
        valores=linha(r)
        view+='<tr>'+''.join('<td>'+esc(valores[i] or '—')+'</td>' for i in idx)+'<td><details><summary>Ver nota</summary>'+''.join(f'<p>{COLUNAS[i]}: {esc(valores[i] or "—")}</p>' for i in [2,4,9,11,13])+'</details></td></tr>'
    return pagina('Abastecimentos',view+'</tbody></table></div><p class="rodape">Média por intervalo entre lançamentos de diesel. O primeiro registro da placa fica sem média. O resumo usa a distância total dividida pelos litros dos intervalos válidos.</p>')
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
        f={k:v[-1] for k,v in parse_qs(u.query).items() if k in ('placa','modelo','de','ate')}
        if u.path=='/novo': return self.enviar(formulario())
        if u.path=='/estilo.css': return self.enviar((ROOT/'estilo.css').read_text(encoding='utf8'),'text/css')
        if u.path not in ('/','/csv'): return self.send_error(404)
        try: rows=consultar(f)
        except ValueError: return self.enviar(pagina('Consulta','<p>Confira o período informado.</p><a href="/">Voltar</a>'),status=422)
        if u.path=='/': return self.enviar(relatorio(f,rows))
        out=io.StringIO(); writer=csv.writer(out,delimiter=';'); writer.writerow(COLUNAS)
        for r in rows: writer.writerow(["'"+v if v.lstrip().startswith(('=','+','-','@')) else v for v in linha(r)])
        self.enviar('\ufeff'+out.getvalue(),'text/csv; charset=utf-8')
    def do_POST(self):
        if not self.permitido(): return
        if self.path!='/salvar': return self.send_error(404)
        n=int(self.headers.get('Content-Length','0'))
        if not 0<n<=32000: return self.send_error(413)
        f={k:v[-1] for k,v in parse_qs(self.rfile.read(n).decode(),keep_blank_values=True).items()}
        if not secrets.compare_digest(f.get('token',''),TOKEN): return self.send_error(403)
        try: gravar(f)
        except (KeyError,ValueError,InvalidOperation) as e: return self.enviar(formulario(f,str(e)),status=422)
        self.send_response(303); self.send_header('Location','/'); self.end_headers()
if __name__=='__main__':
    iniciar()
    with ThreadingHTTPServer(('127.0.0.1',PORT),Handler) as servidor:
        url=f'http://127.0.0.1:{PORT}'
        print('FrotaGestor em '+url,flush=True)
        if os.environ.get('FROTA_SEM_NAVEGADOR')!='1': webbrowser.open(url)
        servidor.serve_forever()
