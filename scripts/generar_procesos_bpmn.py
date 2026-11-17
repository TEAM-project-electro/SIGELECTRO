"""Modelos BPMN 2.0 descriptivos del SIG; no ejecuta ni modifica la aplicación."""
from pathlib import Path
from lxml import etree as E
import json
import textwrap
import html
import hashlib
import zipfile
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'docs/procesos_bpmn'
NS = {'bpmn': 'http://www.omg.org/spec/BPMN/20100524/MODEL',
      'bpmndi': 'http://www.omg.org/spec/BPMN/20100524/DI',
      'dc': 'http://www.omg.org/spec/DD/20100524/DC',
      'di': 'http://www.omg.org/spec/DD/20100524/DI',
      'xsi': 'http://www.w3.org/2001/XMLSchema-instance'}

def tag(kind):
    p, n = kind.split(':')
    return '{'+NS[p]+'}'+n

def element(parent, kind, text=None, **attrs):
    result = E.SubElement(parent, tag(kind), {k: str(v) for k,v in attrs.items()})
    if text is not None: result.text = text
    return result

class Model:
    def __init__(self, code, slug, name, lanes, purpose, sources, category='Operación'):
        self.code, self.slug, self.name = code, slug, name
        self.lanes, self.purpose, self.sources, self.category = lanes, purpose, sources, category
        self.nodes, self.flows = {}, []

    def node(self, key, kind, name, lane, col, row=0, note=''):
        self.nodes[key] = dict(kind=kind, name=name, lane=lane, col=col, row=row, note=note)
        return self

    def flow(self, source, target, name='', default=False, condition=''):
        self.flows.append(dict(source=source, target=target, name=name, default=default, condition=condition))
        return self

    def chain(self, *keys):
        for a,b in zip(keys, keys[1:]): self.flow(a,b)
        return self

    def coords(self, node):
        w,h = (36,36) if node['kind'] in ('startEvent','endEvent') else ((50,50) if node['kind']=='exclusiveGateway' else (150,70))
        cx = 175 + node['col']*205
        cy = 220 + node['lane']*260 + node['row']*90
        return cx-w/2, cy-h/2, w,h

    def points(self, flow):
        a, b = self.nodes[flow['source']], self.nodes[flow['target']]
        x,y,w,h=self.coords(a); xx,yy,ww,hh=self.coords(b)
        ax,ay,bx,by=x+w/2,y+h/2,xx+ww/2,yy+hh/2
        if abs(ax-bx)<1:
            return [(ax,y+h if by>ay else y),(bx,yy if by>ay else yy+hh)]
        if bx>ax:
            if abs(ay-by)<1:
                if b['col']-a['col']>1:
                    rail=90+a['lane']*260+35
                    return [(ax,y),(ax,rail),(bx,rail),(bx,yy)]
                return [(x+w,ay),(xx,by)]
            if a['kind']=='exclusiveGateway':
                return [(ax,y if by<ay else y+h),(ax,by),(xx,by)]
            mid=(x+w+xx)/2
            return [(x+w,ay),(mid,ay),(mid,by),(xx,by)]
        # El retorno corre por el margen superior de su carril, nunca por el centro de tareas.
        rail=90+min(a['lane'],b['lane'])*260+22
        gap=x-25
        return [(x,ay),(gap,ay),(gap,rail),(bx,rail),(bx,yy)]

    def save(self):
        prefix=self.code+'_'
        rid=lambda key: prefix+key
        definitions=E.Element(tag('bpmn:definitions'), nsmap=NS, id=rid('Definitions'),
            targetNamespace='urn:academico:sig-electrocentro:procesos', exporter='SIG Electrocentro — documentación académica', exporterVersion='1.0')
        process=element(definitions,'bpmn:process',id=rid('Process'),name=self.name,isExecutable='false')
        element(process,'bpmn:documentation',self.purpose+'\nFuentes del sistema: '+', '.join(self.sources)+
            '\nModelo descriptivo AS-IS: no incluye aprobaciones institucionales ni automatización de Bonita. Sesión activa requerida salvo P01 y operación técnica local P13.')
        lane_set=element(process,'bpmn:laneSet',id=rid('LaneSet'))
        for i,name in enumerate(self.lanes):
            lane=element(lane_set,'bpmn:lane',id=rid('Lane'+str(i)),name=name)
            for key,node in self.nodes.items():
                if node['lane']==i: element(lane,'bpmn:flowNodeRef',rid(key))
        flow_ids={i:rid('Flow'+str(i+1)) for i in range(len(self.flows))}
        for key,node in self.nodes.items():
            attrs=dict(id=rid(key),name=node['name'])
            incoming=[i for i,f in enumerate(self.flows) if f['target']==key]
            outgoing=[i for i,f in enumerate(self.flows) if f['source']==key]
            if node['kind']=='exclusiveGateway':
                attrs['gatewayDirection']='Diverging' if len(outgoing)>1 else 'Converging'
                default=next((i for i in outgoing if self.flows[i]['default']),None)
                if default is not None: attrs['default']=flow_ids[default]
            shape=element(process,'bpmn:'+node['kind'],**attrs)
            if node['note']: element(shape,'bpmn:documentation',node['note'])
            for i in incoming: element(shape,'bpmn:incoming',flow_ids[i])
            for i in outgoing: element(shape,'bpmn:outgoing',flow_ids[i])
        for i,f in enumerate(self.flows):
            attrs=dict(id=flow_ids[i],sourceRef=rid(f['source']),targetRef=rid(f['target']))
            if f['name']:attrs['name']=f['name']
            sf=element(process,'bpmn:sequenceFlow',**attrs)
            if f['condition']:
                cond=element(sf,'bpmn:conditionExpression',f['condition'])
                cond.set(tag('xsi:type'),'bpmn:tFormalExpression')
        collaboration=element(definitions,'bpmn:collaboration',id=rid('Collaboration'))
        element(collaboration,'bpmn:participant',id=rid('Pool'),name='SIG Electrocentro — '+self.code,processRef=rid('Process'))
        diagram=element(definitions,'bpmndi:BPMNDiagram',id=rid('Diagram'),name=self.name)
        plane=element(diagram,'bpmndi:BPMNPlane',id=rid('Plane'),bpmnElement=rid('Collaboration'))
        width=max(n['col'] for n in self.nodes.values())*205+300
        height=len(self.lanes)*260
        pool=element(plane,'bpmndi:BPMNShape',id=rid('PoolShape'),bpmnElement=rid('Pool'),isHorizontal='true')
        element(pool,'dc:Bounds',x=35,y=90,width=width,height=height)
        for i in range(len(self.lanes)):
            shape=element(plane,'bpmndi:BPMNShape',id=rid('LaneShape'+str(i)),bpmnElement=rid('Lane'+str(i)),isHorizontal='true')
            element(shape,'dc:Bounds',x=65,y=90+i*260,width=width-30,height=260)
        for key,node in self.nodes.items():
            attrs=dict(id=rid(key+'_di'),bpmnElement=rid(key))
            if node['kind']=='exclusiveGateway': attrs['isMarkerVisible']='true'
            shape=element(plane,'bpmndi:BPMNShape',**attrs)
            x,y,w,h=self.coords(node)
            element(shape,'dc:Bounds',x=x,y=y,width=w,height=h)
            if node['kind'] in ('startEvent','endEvent','exclusiveGateway'):
                label=element(shape,'bpmndi:BPMNLabel')
                lx,ly=(x+w/2-150,y+h/2-95) if node['kind']=='exclusiveGateway' else (x+w/2-75,y+h+7)
                element(label,'dc:Bounds',x=lx,y=ly,width=150,height=40)
        for i,f in enumerate(self.flows):
            edge=element(plane,'bpmndi:BPMNEdge',id=rid('Edge'+str(i+1)),bpmnElement=flow_ids[i])
            pts=self.points(f)
            for x,y in pts: element(edge,'di:waypoint',x=x,y=y)
            if f['name']:
                label=element(edge,'bpmndi:BPMNLabel')
                element(label,'dc:Bounds',x=(pts[0][0]+pts[1][0])/2-35,y=(pts[0][1]+pts[1][1])/2-21,width=95,height=18)
        stem=self.code+'_'+self.slug
        file=OUT/'diagramas'/f'{stem}.bpmn'
        E.ElementTree(definitions).write(str(file),encoding='UTF-8',xml_declaration=True,pretty_print=True)
        self.svg(OUT/'vistas'/f'{stem}.svg',width,height)
        return file

    def svg(self, file, width, height):
        W,H=width+80,height+150
        esc=html.escape
        parts=[f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">',
            '<defs><marker id="arrow" markerWidth="10" markerHeight="8" refX="9" refY="4" orient="auto"><path d="M0,0 L10,4 L0,8 Z" fill="#334155"/></marker></defs>',
            f'<rect width="{W}" height="{H}" fill="white"/>',
            f'<text x="35" y="30" font-family="Arial" font-size="20" font-weight="bold" fill="#0f172a">{esc(self.code+" · "+self.name)}</text>',
            '<text x="35" y="53" font-family="Arial" font-size="12" fill="#475569">Modelo AS-IS académico · BPMN 2.0 · Las rutas representan el sistema implementado</text>',
            f'<rect x="35" y="90" width="{width}" height="{height}" fill="none" stroke="#334155"/>']
        for i,name in enumerate(self.lanes):
            y=90+i*260
            parts += [f'<rect x="65" y="{y}" width="{width-30}" height="260" fill="{("#f8fafc" if i%2==0 else "#ffffff")}" stroke="#94a3b8"/>',
                f'<text transform="translate(87,{y+130}) rotate(-90)" text-anchor="middle" font-family="Arial" font-size="12" fill="#334155">{esc(name)}</text>']
        for f in self.flows:
            pts=self.points(f)
            parts.append('<polyline points="'+' '.join(f'{x},{y}' for x,y in pts)+'" fill="none" stroke="#334155" stroke-width="1.4" marker-end="url(#arrow)"/>')
            if f['name']:
                x=(pts[0][0]+pts[1][0])/2; y=(pts[0][1]+pts[1][1])/2-9
                parts.append(f'<text x="{x}" y="{y}" text-anchor="middle" font-family="Arial" font-size="11" fill="#0f172a" style="paint-order:stroke;stroke:white;stroke-width:4">{esc(f["name"])}</text>')
        for node in self.nodes.values():
            x,y,w,h=self.coords(node); cx=x+w/2; cy=y+h/2
            kind=node['kind']
            if kind in ('startEvent','endEvent'):
                parts.append(f'<circle cx="{cx}" cy="{cy}" r="18" fill="white" stroke="#0f172a" stroke-width="{3.5 if kind=="endEvent" else 1.5}"/>')
            elif kind=='exclusiveGateway':
                parts.append(f'<path d="M{cx},{y} L{x+w},{cy} L{cx},{y+h} L{x},{cy} Z" fill="#fffbeb" stroke="#334155"/><path d="M{cx-7},{cy-7} L{cx+7},{cy+7} M{cx+7},{cy-7} L{cx-7},{cy+7}" stroke="#334155" stroke-width="2"/>')
            else:
                parts.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="10" fill="{("#eff6ff" if kind=="userTask" else "white")}" stroke="#334155" stroke-width="1.4"/>')
                if kind=='userTask': parts.append(f'<circle cx="{x+13}" cy="{y+10}" r="4" fill="none" stroke="#334155"/><path d="M{x+6},{y+23} v-4 q7,-8 14,0 v4 Z" fill="none" stroke="#334155"/>')
                if kind=='serviceTask':parts.append(f'<text x="{x+5}" y="{y+18}" font-family="Segoe UI Symbol,Arial" font-size="19">⚙</text>')
            lines=textwrap.wrap(node['name'], width=23)
            ty=y+h+20 if kind in ('startEvent','endEvent') else (cy-83 if kind=='exclusiveGateway' else y+38)
            tx=cx-75 if kind=='exclusiveGateway' else cx
            for j,line in enumerate(lines):parts.append(f'<text x="{tx}" y="{ty+j*14}" font-family="Arial" font-size="11.5" text-anchor="middle" fill="#0f172a">{esc(line)}</text>')
        parts.append('</svg>')
        file.write_text('\n'.join(parts),encoding='utf-8')
        self.png(file.with_suffix('.png'),width,height)

    def png(self,file,width,height):
        import math
        im=Image.new('RGB',(int(width+80),int(height+150)),'white'); d=ImageDraw.Draw(im)
        font=ImageFont.truetype('C:/Windows/Fonts/arial.ttf',12)
        title=ImageFont.truetype('C:/Windows/Fonts/arialbd.ttf',20)
        d.text((35,12),self.code+' · '+self.name,font=title,fill='#0f172a')
        d.text((35,40),'Modelo AS-IS académico · BPMN 2.0 · Vista de lectura del archivo .bpmn',font=font,fill='#475569')
        d.rectangle((35,90,width+35,height+90),outline='#334155')
        for i,name in enumerate(self.lanes):
            y=90+i*260
            d.rectangle((65,y,width+35,y+260),fill='#f8fafc' if i%2==0 else 'white',outline='#94a3b8')
            label=Image.new('RGBA',(250,22),'#ffffff00'); ld=ImageDraw.Draw(label)
            ld.text((125,1),name,font=font,fill='#334155',anchor='mt')
            label=label.rotate(90,expand=True)
            im.paste(label,(69,int(y+5)),label)
        for flow in self.flows:
            pts=self.points(flow);d.line(pts,fill='#334155',width=2)
            x,y=pts[-1];px,py=pts[-2];ang=math.atan2(y-py,x-px)
            d.polygon([(x,y),(x-9*math.cos(ang-.45),y-9*math.sin(ang-.45)),(x-9*math.cos(ang+.45),y-9*math.sin(ang+.45))],fill='#334155')
            if flow['name']:
                lx=(pts[0][0]+pts[1][0])/2;ly=(pts[0][1]+pts[1][1])/2-17
                box=d.textbbox((lx,ly),flow['name'],font=font,anchor='mt');d.rectangle(box,fill='white')
                d.text((lx,ly),flow['name'],font=font,fill='#0f172a',anchor='mt')
        for n in self.nodes.values():
            x,y,w,h=self.coords(n);cx=x+w/2;cy=y+h/2;k=n['kind']
            if k in ('startEvent','endEvent'):d.ellipse((x,y,x+w,y+h),fill='white',outline='#0f172a',width=4 if k=='endEvent' else 2)
            elif k=='exclusiveGateway':
                d.polygon([(cx,y),(x+w,cy),(cx,y+h),(x,cy)],fill='#fffbeb',outline='#334155')
                d.line([(cx-7,cy-7),(cx+7,cy+7)],fill='#334155',width=2);d.line([(cx-7,cy+7),(cx+7,cy-7)],fill='#334155',width=2)
            else:
                d.rounded_rectangle((x,y,x+w,y+h),radius=10,fill='#eff6ff' if k=='userTask' else 'white',outline='#334155',width=2)
                if k=='userTask':
                    d.ellipse((x+8,y+5,x+16,y+13),outline='#334155');d.line([(x+5,y+25),(x+5,y+19),(x+12,y+14),(x+20,y+19),(x+20,y+25),(x+5,y+25)],fill='#334155')
                elif k=='serviceTask':d.text((x+6,y+4),'⚙',font=ImageFont.truetype('C:/Windows/Fonts/seguisym.ttf',19),fill='#334155')
            lines=textwrap.wrap(n['name'],width=23)
            top=y+h+8 if k in ('startEvent','endEvent') else (cy-95 if k=='exclusiveGateway' else y+27)
            tx=cx-75 if k=='exclusiveGateway' else cx
            for j,line in enumerate(lines):d.text((tx,top+j*14),line,font=font,fill='#0f172a',anchor='mt')
        im.save(file)

MODELS=[]
def new(code,slug,name,lanes,purpose,sources,category='Operación'):
    model=Model(code,slug,name,lanes,purpose,sources,category); MODELS.append(model); return model

def write_model(code,slug,name,actor,input_name,validation,save_name,sources,note):
    m=new(code,slug,name,[actor,'Sistema SIG'],note,sources)
    m.node('Inicio','startEvent','Necesidad de actualización',0,0)
    m.node('Datos','userTask',input_name,0,1,note='Incluye revisión esperada al editar. El actor proviene de la sesión; nunca del formulario.')
    m.node('Validar','serviceTask',validation,1,2,note=note)
    m.node('Valido','exclusiveGateway','¿Solicitud válida y autorizada?',1,3)
    m.node('Corregir','userTask','Revisar rechazo y corregir',0,4,1,note='Sin autorización no se permite guardar. Un conflicto exige recargar la revisión vigente.')
    m.node('Guardar','serviceTask',save_name,1,4,note='Una sola transacción: bloquear revisión central y padre si corresponde; revalidar actor, perfil, propiedad y revisión; guardar negocio, auditoría, motivo y actividad; COMMIT o ROLLBACK. Limpiar actor y motivo en finally.')
    m.node('Confirmado','exclusiveGateway','¿Transacción confirmada?',1,5)
    m.node('Fallo','serviceTask','Revertir y comunicar conflicto',1,6,1,note='El cambio y su auditoría se revierten juntos. Mostrar mensaje de negocio y limpiar contexto de conexión.')
    m.node('Resultado','userTask','Consultar cambio confirmado',0,6)
    m.node('Fin','endEvent','Registro actualizado',0,7)
    m.chain('Inicio','Datos','Validar','Valido')
    m.flow('Valido','Guardar','Sí',condition='solicitud_valida == true').flow('Valido','Corregir','No',default=True)
    m.chain('Guardar','Confirmado')
    m.flow('Confirmado','Resultado','Sí',condition='transaccion_confirmada == true').flow('Confirmado','Fallo','No',default=True)
    m.chain('Fallo','Corregir','Datos').chain('Resultado','Fin')
    return m

# P00 — visión global: no supone que cada visita haga todas las operaciones.
m=new('P00','seguimiento_integral','Seguimiento gerencial de proyectos',['Gestor autorizado','Sistema SIG'],
    'Vista de contexto. El gestor puede registrar seguimiento de hitos o cortes y consultar resultados en cualquier orden. No representa una aprobación institucional ni una obligación de ejecutar todos los módulos en cada visita.',
    ['sistema/routes/web.php','sistema/app/Policies/ProyectoPolicy.php'],'Visión integral')
for args in [
    ('I','startEvent','Necesidad de seguimiento',0,0),('Cartera','userTask','Seleccionar proyecto de cartera',0,1),
    ('Estado','serviceTask','Consultar estado y permisos',1,2),('Accion','exclusiveGateway','¿Qué acción corresponde?',0,3),
    ('Hitos','userTask','Gestionar hitos — P03',0,4),('Cortes','userTask','Registrar corte — P04',0,4,1),
    ('Consultar','userTask','Consultar indicadores — P07',0,5),('Cierre','exclusiveGateway','¿Se solicita cerrar?',0,6),
    ('Cerrar','userTask','Solicitar cierre formal — P05',0,7),('Fin','endEvent','Seguimiento realizado',0,8),
    ('Mostrar','serviceTask','Mostrar indicadores y cobertura',1,6)]:m.node(*args)
m.chain('I','Cartera','Estado','Accion')
m.flow('Accion','Hitos','Hitos',condition='accion == "hitos"').flow('Accion','Cortes','Presupuesto',condition='accion == "presupuesto"').flow('Accion','Consultar','Consulta',default=True)
m.chain('Hitos','Consultar').chain('Cortes','Consultar').chain('Consultar','Mostrar','Cierre')
m.flow('Cierre','Cerrar','Sí',condition='solicita_cierre == true').flow('Cierre','Fin','No',default=True).chain('Cerrar','Fin')

m=new('P01','acceso','Acceso y cierre de sesión',['Usuario: cualquier perfil','Sistema SIG'],
    'Autenticación de Administrador, Responsable y Consulta. Cuenta activa, limitación de cinco intentos por usuario/IP, regeneración de sesión y cierre explícito. No hay registro público ni aprobación de acceso.',
    ['sistema/app/Http/Requests/Auth/LoginRequest.php','sistema/app/Http/Controllers/Auth/LoginController.php','sistema/app/Http/Middleware/VerificarUsuarioActivo.php'],'Soporte')
for a in [('I','startEvent','Usuario requiere acceso',0,0),('Cred','userTask','Ingresar usuario y clave',0,1),('Intentos','serviceTask','Comprobar límite de intentos',1,2),('Permitido','exclusiveGateway','¿Intento permitido?',1,3),('Bloq','endEvent','Acceso temporalmente limitado',1,4,1),('Auth','serviceTask','Validar credenciales y activo',1,4),('Ok','exclusiveGateway','¿Identidad válida?',1,5),('Error','serviceTask','Informar error y contar intento',1,6,1),('Sesion','serviceTask','Regenerar sesión y cargar perfil',1,6),('Usar','userTask','Utilizar módulos autorizados',0,7),('Salir','userTask','Solicitar cierre de sesión',0,8),('Invalidar','serviceTask','Invalidar sesión y token CSRF',1,9),('Fin','endEvent','Sesión cerrada',0,10)]:m.node(*a)
m.chain('I','Cred','Intentos','Permitido').flow('Permitido','Auth','Sí',condition='limite_excedido == false').flow('Permitido','Bloq','No',default=True)
m.chain('Auth','Ok').flow('Ok','Sesion','Sí',condition='credenciales_validas && cuenta_activa').flow('Ok','Error','No',default=True).chain('Error','Cred').chain('Sesion','Usar','Salir','Invalidar','Fin')

write_model('P02','cartera','Registrar o editar proyectos','Administrador / Responsable titular',
    'Registrar o editar ficha de proyecto','Validar código, datos y campos por perfil','Guardar proyecto y auditoría',
    ['sistema/app/Http/Controllers/ProyectoController.php','sistema/app/Http/Requests/Proyecto/UpdateProyectoRequest.php','sistema/app/Policies/ProyectoPolicy.php'],
    'Alta reservada a Administrador; edición del Responsable sólo sobre su proyecto y campos permitidos. Código PGD único; monto no negativo; objetivo y responsable existentes. Reducción exige motivo y no puede invalidar cortes anteriores. La baja física es excepcional y no forma parte del flujo habitual de cartera; se bloquea si hay referencias.')

write_model('P03','hitos_linea_base','Gestionar hitos y línea base','Administrador / Responsable titular',
    'Registrar hito, avance o cierre','Validar propiedad, fechas y línea base','Guardar hito, actividad y auditoría',
    ['sistema/app/Http/Controllers/HitoController.php','sistema/app/Http/Requests/Hito/UpdateHitoRequest.php','sistema/app/Policies/HitoPolicy.php'],
    'Proyecto no cerrado, permisos por propietario, revisión vigente al editar. Registrar descripción, fecha prevista, fecha de cierre y evidencia cuando corresponda. Cambiar pertenencia a línea base exige motivo explícito; no se inventa aprobación de un comité. Los hitos fuera de línea base no alteran avance ni retraso del KPI.')

write_model('P04','avance_presupuestal','Registrar o editar cortes presupuestales','Administrador / Responsable titular',
    'Registrar o editar importes del corte','Validar unicidad y reglas R1–R2','Guardar corte, actividad y auditoría',
    ['sistema/app/Http/Controllers/AvancePresupuestalController.php','sistema/app/Http/Requests/Avance/UpdateAvanceRequest.php','sistema/database/migrations/2026_11_03_000002_create_triggers_y_procedimientos.php'],
    'Proyecto no cerrado y usuario autorizado. R1: planificado/gasto no negativos ni superiores al monto del proyecto. R2: gasto acumulado no decreciente contra corte anterior y posterior. Fecha única por proyecto; edición con revisión esperada. Gasto mayor al plan está permitido si no excede el monto del proyecto. No se modelan desembolsos ni aprobaciones financieras.')

m=new('P05','cierre_proyecto','Cerrar formalmente un proyecto',['Administrador / Responsable titular','Sistema SIG'],
    'El cierre es una solicitud ejecutada por el titular o Administrador. No interviene un aprobador adicional. La regla implementada exige al menos un hito de línea base y ningún hito de línea base sin fecha de cierre.',
    ['sistema/app/Http/Controllers/ProyectoController.php','sistema/app/Http/Requests/Proyecto/CerrarProyectoRequest.php','sistema/database/migrations/2026_11_03_000002_create_triggers_y_procedimientos.php'])
for a in [('I','startEvent','Proyecto candidato a cierre',0,0),('Solicitar','userTask','Solicitar cierre formal',0,1),('Validar','serviceTask','Bloquear y revalidar permiso',1,2),('Permiso','exclusiveGateway','¿Puede cerrar este proyecto?',1,3),('Denegado','endEvent','Cierre denegado',1,4,1),('LB','serviceTask','Comprobar hitos de línea base',1,4),('Completo','exclusiveGateway','¿Hay línea base y todos cerrados?',1,5),('Observar','serviceTask','Revertir e informar pendientes',1,6,1),('Atender','userTask','Actualizar hitos y revisar',0,6,1),('Guardar','serviceTask','Cerrar y auditar en transacción',1,6),('Fin','endEvent','Proyecto cerrado',0,7)]:m.node(*a)
m.chain('I','Solicitar','Validar','Permiso').flow('Permiso','LB','Sí',condition='permiso_cierre == true').flow('Permiso','Denegado','No',default=True)
m.chain('LB','Completo').flow('Completo','Guardar','Sí',condition='hitos_lb > 0 && hitos_lb_sin_cierre == 0').flow('Completo','Observar','No',default=True).chain('Observar','Atender','Solicitar').chain('Guardar','Fin')
m.nodes['Guardar']['note']='Una transacción guarda estado Cerrado, actividad y auditoría; ante error se revierte y se informa, sin cierre parcial. El control de estado/permisos ocurre después del bloqueo.'

write_model('P06','reapertura','Reabrir un proyecto cerrado','Administrador',
    'Seleccionar proyecto e ingresar motivo','Validar perfil, estado y justificación','Reabrir a En curso y auditar',
    ['sistema/app/Http/Controllers/ProyectoController.php','sistema/app/Http/Requests/Proyecto/ReabrirProyectoRequest.php','sistema/app/Policies/ProyectoPolicy.php'],
    'Sólo Administrador y sólo proyecto actualmente Cerrado. Motivo obligatorio auditado en bitacora_motivo. Operación atómica con actualización de revisión; un Responsable no puede reabrir su propio proyecto.')

m=new('P07','consulta_indicadores','Consultar cartera e indicadores',['Usuario: cualquier perfil','Sistema SIG'],
    'Consultar la cartera con filtros de estado, responsable, objetivo, búsqueda y fecha. Elegir operativo o DW y modo temporal. No se guarda información de negocio ni se ejecuta ETL automáticamente.',
    ['sistema/app/Http/Controllers/TableroController.php','sistema/app/Services/IndicadoresService.php','sistema/app/Services/EtlService.php'])
for a in [('I','startEvent','Necesidad de información',0,0),('Filtros','userTask','Elegir filtros, fecha y origen',0,1),('Validar','serviceTask','Validar sesión y parámetros',1,2),('Origen','exclusiveGateway','¿Origen seleccionado?',1,3),('Op','serviceTask','Leer snapshot operativo',1,4),('DW','serviceTask','Leer snapshot DW y revisión',1,4,1),('Merge','exclusiveGateway','Unificar datos del origen',1,5),('Calcular','serviceTask','Calcular KPIs y coberturas',1,6),('Mostrar','serviceTask','Mostrar resultados y vigencia',1,7),('Revisar','userTask','Analizar cifras y detalle',0,8),('Otra','exclusiveGateway','¿Cambiar consulta?',0,9),('Fin','endEvent','Consulta realizada',0,10)]:m.node(*a)
m.chain('I','Filtros','Validar','Origen').flow('Origen','Op','Operativo',condition='origen == "OPERATIVO"').flow('Origen','DW','DW',default=True)
m.chain('Op','Merge').chain('DW','Merge').chain('Merge','Calcular','Mostrar','Revisar','Otra').flow('Otra','Filtros','Sí',condition='cambiar_consulta == true').flow('Otra','Fin','No',default=True)
m.nodes['Calcular']['note']='CORTE_COMUN: hitos evaluados a fecha común; finanzas sólo con corte exacto. ULTIMO_REPORTE_POR_PROYECTO: máximo reporte <= fecha límite de cada proyecto. Mostrar Sin datos, No aplica, cobertura y exclusiones; hitos fuera de LB no generan retraso. No promediar porcentajes individuales.'
m.nodes['Mostrar']['note']='Si DW está desactualizado se advierte, sin cargarlo automáticamente. Una consulta sin proyectos devuelve conteos cero y porcentajes Sin datos.'

m=new('P08','reportes','Generar y descargar reporte gerencial',['Usuario: cualquier perfil','Sistema SIG'],
    'Reportar el mismo contexto seleccionado en el tablero. Formatos implementados: CSV UTF-8 BOM o vista imprimible para guardar PDF desde el navegador. Una variación de revisión se advierte; no se presenta como archivo idéntico silenciosamente.',
    ['sistema/app/Http/Controllers/ReporteController.php','sistema/resources/views/reportes/imprimir.blade.php'])
for a in [('I','startEvent','Consulta disponible',0,0),('Pedir','userTask','Elegir CSV o vista de impresión',0,1),('Contexto','serviceTask','Capturar filtros, origen y revisión',1,2),('Leer','serviceTask','Consultar datos con ese contexto',1,3),('Revision','exclusiveGateway','¿Coincide revisión solicitada?',1,4),('Avisar','serviceTask','Incluir aviso de datos cambiados',1,5,1),('Merge','exclusiveGateway','Continuar generación',1,6),('Tipo','exclusiveGateway','¿Formato solicitado?',1,7),('CSV','serviceTask','Generar CSV con BOM y protección',1,8),('Imprimir','serviceTask','Renderizar vista imprimible',1,8,1),('Merge2','exclusiveGateway','Entregar salida',1,9),('Recibir','userTask','Descargar o imprimir documento',0,10),('Fin','endEvent','Reporte obtenido',0,11)]:m.node(*a)
m.chain('I','Pedir','Contexto','Leer','Revision').flow('Revision','Merge','Sí',condition='revision_esperada == revision_actual').flow('Revision','Avisar','No',default=True).chain('Avisar','Merge','Tipo')
m.flow('Tipo','CSV','CSV',condition='formato == "CSV"').flow('Tipo','Imprimir','Impresión',default=True).chain('CSV','Merge2').chain('Imprimir','Merge2').chain('Merge2','Recibir','Fin')
m.nodes['CSV']['note']='Neutralizar texto interpretable como fórmula sin alterar importes negativos legítimos. Incluir filtros, modo temporal, revisión y aviso de datos demostrativos; no exportar credenciales.'

m=new('P09','actualizacion_dw','Actualizar el almacén de datos — ETL',['Administrador','Sistema SIG'],
    'Carga manual desde la aplicación o comando local etl:cargar. Mantiene mutex y transacción para publicar dimensiones, hechos y revisión. No modela trabajo en Power BI ni una programación periódica no implementada.',
    ['sistema/app/Http/Controllers/EtlController.php','sistema/app/Services/EtlService.php','sistema/database/migrations/2026_11_03_000004_create_dw_procedimientos_y_vistas.php'],'Soporte')
for a in [('I','startEvent','DW requiere actualización',0,0),('Pedir','userTask','Solicitar ejecución ETL',0,1),('Perfil','serviceTask','Verificar Administrador',1,2),('Lock','serviceTask','Intentar adquirir mutex ETL',1,3),('Disponible','exclusiveGateway','¿Mutex adquirido?',1,4),('Ocupado','endEvent','Carga no iniciada',1,5,1),('Origen','serviceTask','Iniciar transacción y bloquear revisión',1,5),('Dims','serviceTask','Sincronizar calendario y dimensiones',1,6),('Hechos','serviceTask','Cargar hechos y depurar ausentes',1,7),('Ok','exclusiveGateway','¿Carga completa sin error?',1,8),('Rollback','serviceTask','Revertir y registrar fallo',1,9,1),('Commit','serviceTask','Publicar datos y revisión en COMMIT',1,9),('Merge','exclusiveGateway','Finalizar ejecución',1,10),('Liberar','serviceTask','Liberar mutex e informar resultado',1,11),('Fin','endEvent','Estado de carga informado',0,12)]:m.node(*a)
m.chain('I','Pedir','Perfil','Lock','Disponible').flow('Disponible','Origen','Sí',condition='mutex_adquirido == true').flow('Disponible','Ocupado','No',default=True)
m.chain('Origen','Dims','Hechos','Ok').flow('Ok','Commit','Sí',condition='carga_sin_error == true').flow('Ok','Rollback','No',default=True).chain('Commit','Merge').chain('Rollback','Merge').chain('Merge','Liberar','Fin')
m.nodes['Ocupado']['note']='Si no se obtiene mutex dentro del tiempo configurado, devolver fallo sin modificar dimensiones/hechos. Una solicitud sin permiso termina denegada antes de ejecutar el procedimiento.'
m.nodes['Ok']['note']='Abstracción del EXIT HANDLER SQL: cualquier excepción en la fase transaccional, incluso en calendario/dimensiones, recorre rollback; no se presupone que siempre se alcance la última inserción.'
for node in m.nodes.values():
    if node['col']>=3:node['col']+=2
m.node('Autorizado','exclusiveGateway','¿Perfil autorizado?',1,3)
m.node('Denegado','endEvent','Ejecución denegada',1,4,1)
for flow in m.flows:
    if flow['source']=='Perfil' and flow['target']=='Lock':flow['target']='Autorizado'
m.flow('Autorizado','Lock','Sí',condition='perfil == "Administrador"').flow('Autorizado','Denegado','No',default=True)

write_model('P10','administracion_usuarios','Administrar usuarios y perfiles','Administrador',
    'Crear usuario o editar perfil/estado','Validar datos, motivo y último Admin','Guardar usuario y evento auditado',
    ['sistema/app/Http/Controllers/ResponsableController.php','sistema/app/Services/AdministrarUsuarios.php','sistema/app/Policies/ResponsablePolicy.php'],
    'Usuario único; perfiles Administrador/Responsable/Consulta. Clave requerida al crear y opcional al editar. Desactivar exige motivo de 5 a 200 caracteres. Bloquear revisión y administradores activos para impedir desactivar/degradar al último. El hash se guarda pero nunca se serializa ni aparece en auditoría. La desactivación revoca el acceso en la siguiente petición.')
MODELS[-1].category='Soporte'

write_model('P11','catalogo_objetivos','Administrar objetivos de gobierno digital','Administrador',
    'Crear o editar código y descripción','Validar permiso, unicidad y longitudes','Guardar catálogo y revisión auditada',
    ['sistema/app/Http/Controllers/ObjetivoGdController.php','sistema/app/Policies/ObjetivoGdPolicy.php'],
    'El catálogo implementa alta y edición por Administrador. Los otros perfiles sólo consultan. Código único; descripción hasta 200 caracteres. El cambio incrementa la revisión para invalidar consultas y advertir desactualización del DW. No se modela una aprobación institucional ni una eliminación de catálogo no expuesta por rutas.')
MODELS[-1].category='Soporte'

m=new('P12','consulta_auditoria','Consultar trazabilidad de cambios',['Administrador','Sistema SIG'],
    'La auditoría se genera automáticamente al cambiar negocio. Este proceso sólo consulta; no permite editar, borrar o aprobar eventos. No crea una segunda auditoría manual.',
    ['sistema/app/Http/Controllers/AuditoriaController.php','sistema/app/Policies/BitacoraCambioPolicy.php'],'Soporte')
for a in [('I','startEvent','Necesidad de trazabilidad',0,0),('Elegir','userTask','Abrir auditoría y elegir filtros',0,1),('Validar','serviceTask','Validar sesión y perfil',1,2),('Permitido','exclusiveGateway','¿Es Administrador?',1,3),('Denegado','endEvent','Consulta denegada',1,4,1),('Leer','serviceTask','Consultar eventos y motivos',1,4),('Mostrar','serviceTask','Mostrar cambios sin credenciales',1,5),('Revisar','userTask','Revisar actor, fecha y valores',0,6),('Fin','endEvent','Trazabilidad consultada',0,7)]:m.node(*a)
m.chain('I','Elegir','Validar','Permitido').flow('Permitido','Leer','Sí',condition='perfil == "Administrador"').flow('Permitido','Denegado','No',default=True).chain('Leer','Mostrar','Revisar','Fin')

m=new('P13','respaldo_restauracion','Respaldar y verificar recuperación local',['Operador técnico local','Herramientas locales / MariaDB'],
    'Proceso técnico operado desde PowerShell con cuenta migradora autorizada; no existe un botón web de respaldo ni se crea un cuarto perfil de aplicación. El camino normal restaura en esquemas descartables y nunca sobrescribe la operativa.',
    ['scripts/respaldo_db.ps1','scripts/restaurar_db.ps1','scripts/ajustar_permisos.php','sistema/tests/Feature/GateG4RestauracionTest.php'],'Soporte técnico')
for a in [('I','startEvent','Necesidad de verificar respaldo',0,0),('Preparar','manualTask','Verificar acceso y destinos locales',0,1),('Dump','serviceTask','Respaldar operativo y DW',1,2),('Revisar','manualTask','Seleccionar respaldo y copias destino',0,3),('Restaurar','serviceTask','Restaurar y remapear referencias',1,4),('Permisos','serviceTask','Reconciliar privilegios técnicos',1,5),('Verificar','serviceTask','Conciliar y probar recorrido en copia',1,6),('Ok','exclusiveGateway','¿Verificaciones satisfactorias?',1,7),('Error','manualTask','Registrar fallo y corregir causa',0,8,1),('Documentar','manualTask','Registrar evidencia de recuperación',0,8),('Fin','endEvent','Copia recuperable verificada',0,9)]:m.node(*a)
m.chain('I','Preparar','Dump','Revisar','Restaurar','Permisos','Verificar','Ok').flow('Ok','Documentar','Sí',condition='verificaciones_aprobadas == true').flow('Ok','Error','No',default=True).chain('Error','Preparar').chain('Documentar','Fin')
m.nodes['Verificar']['note']='Pruebas sobre copia: conteos, montos, restricciones, login, alta auditada, acción denegada y ETL apuntando a copia. Es una ejecución explícita del operador; restaurar_db.ps1 no ejecuta automáticamente toda la suite.'

def validate(paths):
    schema=E.XMLSchema(E.parse(str(OUT/'esquemas/BPMN20.xsd')))
    rows=[]
    for path in paths:
        tree=E.parse(str(path)); schema.assertValid(tree)
        ids=tree.xpath('//@id'); assert len(ids)==len(set(ids)), 'IDs repetidos'
        all_ids=set(ids)
        for attr in ['sourceRef','targetRef','processRef','bpmnElement','default']:
            for value in tree.xpath('//@'+attr): assert value in all_ids,(path.name,attr,value)
        for el in tree.xpath('//bpmn:flowNodeRef|//bpmn:incoming|//bpmn:outgoing',namespaces=NS):assert el.text in all_ids
        process=tree.find('bpmn:process',NS)
        nodes={el.get('id'):el for el in process if E.QName(el).localname in ['startEvent','endEvent','userTask','serviceTask','manualTask','exclusiveGateway']}
        edges=process.findall('bpmn:sequenceFlow',NS)
        starts={k for k,v in nodes.items() if E.QName(v).localname=='startEvent'}
        ends={k for k,v in nodes.items() if E.QName(v).localname=='endEvent'}
        def reach(seed,reverse=False):
            result=set(seed)
            while True:
                added={f.get('sourceRef' if reverse else 'targetRef') for f in edges if f.get('targetRef' if reverse else 'sourceRef') in result}
                if added<=result:return result
                result|=added
        assert set(nodes)<=reach(starts),'Nodos no alcanzables desde inicio'
        assert set(nodes)<=reach(ends,True),'Nodos sin ruta a fin'
        refs=process.xpath('.//bpmn:flowNodeRef/text()',namespaces=NS)
        assert set(refs)==set(nodes) and len(refs)==len(nodes),'Asignación incorrecta de carriles'
        shapes=set(tree.xpath('//bpmndi:BPMNShape/@bpmnElement',namespaces=NS))
        assert set(nodes)<=shapes,'Faltan coordenadas gráficas'
        edge_refs=set(tree.xpath('//bpmndi:BPMNEdge/@bpmnElement',namespaces=NS))
        assert edge_refs=={f.get('id') for f in edges},'Faltan conectores gráficos'
        for key,node in nodes.items():
            incoming=[f for f in edges if f.get('targetRef')==key]
            outgoing=[f for f in edges if f.get('sourceRef')==key]
            kind=E.QName(node).localname
            if kind=='startEvent':assert not incoming and len(outgoing)==1
            elif kind=='endEvent':assert incoming and not outgoing
            else:assert incoming and outgoing
            if kind=='exclusiveGateway' and len(outgoing)>1:
                assert node.get('default')
                for flow in outgoing:
                    if flow.get('id')!=node.get('default'):assert flow.find('bpmn:conditionExpression',NS) is not None
        rows.append({'archivo':path.name,'xsd_omg':'VALIDO','referencias':'VALIDAS','alcanzabilidad':'VALIDA',
            'nodos':len(nodes),'flujos':len(edges),'sha256':hashlib.sha256(path.read_bytes()).hexdigest()})
    (OUT/'validacion_bpmn.json').write_text(json.dumps({'estandar':'BPMN 2.0.2 XML + BPMNDI','importacion_nativa':'NO EJECUTADA en Bizagi/Bonita','archivos':rows},ensure_ascii=False,indent=2),encoding='utf-8')

if __name__=='__main__':
    for folder in ['diagramas','vistas']: (OUT/folder).mkdir(parents=True,exist_ok=True)
    paths=[m.save() for m in MODELS]
    validate(paths)
    catalogue=[dict(codigo=m.code,nombre=m.name,categoria=m.category,archivo=m.code+'_'+m.slug+'.bpmn',carriles=m.lanes,objetivo=m.purpose,fuentes=m.sources) for m in MODELS]
    (OUT/'catalogo_procesos.json').write_text(json.dumps(catalogue,ensure_ascii=False,indent=2),encoding='utf-8')
    links='\n'.join(f'<li><a href="vistas/{m.code}_{m.slug}.svg">{m.code} · {html.escape(m.name)}</a> — <a href="diagramas/{m.code}_{m.slug}.bpmn">BPMN</a></li>' for m in MODELS)
    (OUT/'indice.html').write_text('<!doctype html><html lang="es"><meta charset="utf-8"><title>Procesos SIG Electrocentro</title><style>body{font:16px system-ui;max-width:1100px;margin:40px auto;color:#172033}li{margin:15px 0}a{color:#16498a}</style><h1>Procesos SIG Electrocentro · BPMN 2.0</h1><p>Modelos descriptivos del sistema implementado. Las vistas SVG son ayudas de lectura; importe los archivos .bpmn en el modelador.</p><ol>'+links+'</ol><p>Sin Power BI, importación CSV ni aprobaciones institucionales no implementadas. Validación XML/XSD y referencias; importación nativa en Bizagi/Bonita no ejecutada en este entorno.</p></html>',encoding='utf-8')
    with zipfile.ZipFile(OUT/'SIG_Electrocentro_BPMN_2_0.zip','w',zipfile.ZIP_DEFLATED) as archive:
        for file in sorted(OUT.rglob('*')):
            if file.is_file() and file.suffix!='.zip':archive.write(file,'SIG_Electrocentro_BPMN/'+file.relative_to(OUT).as_posix())
    print(json.dumps({'modelos':len(paths),'validacion':'XSD OMG + referencias + conectividad + BPMNDI aprobadas','carpeta':str(OUT)},ensure_ascii=False))
