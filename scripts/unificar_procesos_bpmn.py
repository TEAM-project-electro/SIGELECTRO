"""Reúne los modelos existentes en un solo lienzo BPMN editable, sin imágenes."""
from pathlib import Path
from copy import deepcopy
from lxml import etree as E
import json
import hashlib

ROOT = Path(__file__).resolve().parent.parent
BASE = ROOT / 'docs/procesos_bpmn'
NS = {
    'bpmn':'http://www.omg.org/spec/BPMN/20100524/MODEL',
    'bpmndi':'http://www.omg.org/spec/BPMN/20100524/DI',
    'dc':'http://www.omg.org/spec/DD/20100524/DC',
    'di':'http://www.omg.org/spec/DD/20100524/DI',
    'xsi':'http://www.w3.org/2001/XMLSchema-instance',
}
def q(prefix,name): return '{'+NS[prefix]+'}'+name

def main():
    sources = sorted((BASE/'diagramas').glob('P*.bpmn'))
    if len(sources)!=14: raise RuntimeError('Se requieren los 14 diagramas originales.')
    docs = [E.parse(str(path)) for path in sources]
    schema = E.XMLSchema(E.parse(str(BASE/'esquemas/BPMN20.xsd')))
    for tree in docs: schema.assertValid(tree)

    root = E.Element(q('bpmn','definitions'),nsmap=NS,id='SIG_Electrocentro_Definitions',
        targetNamespace='urn:academico:sig-electrocentro:procesos',
        exporter='SIG Electrocentro',exporterVersion='1.0')
    for tree in docs: root.append(deepcopy(tree.find('bpmn:process',NS)))
    collaboration = E.SubElement(root,q('bpmn','collaboration'),id='SIG_Electrocentro_Colaboracion',
        name='SIG Electrocentro — modelo completo de procesos')
    E.SubElement(collaboration,q('bpmn','documentation')).text=(
        'Catorce diagramas integrados en un único lienzo editable: una vista de contexto y trece procesos. '
        'Los pools son modelos descriptivos independientes, no etapas obligatorias ni actores externos; '
        'no se inventan flujos de mensaje entre procesos. Sin imágenes ni trabajo en Power BI. '
        'Los procesos no son ejecutables sin configuración adicional del motor.')
    diagram = E.SubElement(root,q('bpmndi','BPMNDiagram'),id='SIG_Electrocentro_Diagrama_Completo',
        name='SIG Electrocentro — 14 procesos')
    plane = E.SubElement(diagram,q('bpmndi','BPMNPlane'),id='SIG_Electrocentro_Lienzo',
        bpmnElement=collaboration.get('id'))
    pools=[]
    for tree in docs:
        participant=tree.find('bpmn:collaboration/bpmn:participant',NS)
        shape=tree.xpath('//bpmndi:BPMNShape[@bpmnElement=$pool]',namespaces=NS,pool=participant.get('id'))[0]
        bounds=shape.find('dc:Bounds',NS)
        pools.append({k:float(bounds.get(k)) for k in ['x','y','width','height']})
    cell_w=max(p['width'] for p in pools)+100
    cell_h=max(p['height'] for p in pools)+90
    for i,(tree,bounds) in enumerate(zip(docs,pools)):
        process=tree.find('bpmn:process',NS)
        participant=deepcopy(tree.find('bpmn:collaboration/bpmn:participant',NS))
        participant.set('name',sources[i].stem.split('_')[0]+' · '+process.get('name'))
        collaboration.append(participant)
        dx=40+(i%2)*cell_w-bounds['x']
        dy=40+(i//2)*cell_h-bounds['y']
        source_plane=tree.find('bpmndi:BPMNDiagram/bpmndi:BPMNPlane',NS)
        for item in source_plane:
            item=deepcopy(item)
            for el in item.iter():
                if E.QName(el).localname in ('Bounds','waypoint'):
                    if el.get('x') is not None:el.set('x',str(float(el.get('x'))+dx))
                    if el.get('y') is not None:el.set('y',str(float(el.get('y'))+dy))
            plane.append(item)

    result=E.ElementTree(root)
    schema.assertValid(result)
    ids=result.xpath('//@id'); known=set(ids)
    assert len(ids)==len(known),'IDs repetidos'
    for attr in ['sourceRef','targetRef','processRef','bpmnElement','default']:
        for value in result.xpath('//@'+attr):assert value in known,(attr,value)
    for el in result.xpath('//bpmn:flowNodeRef|//bpmn:incoming|//bpmn:outgoing',namespaces=NS):assert el.text in known
    assert len(result.findall('bpmn:process',NS))==14
    assert len(collaboration.findall('bpmn:participant',NS))==14
    assert len(result.findall('bpmndi:BPMNDiagram',NS))==1
    # Los conectores y las formas conservan todos los elementos de los originales.
    assert len(plane)==sum(len(t.find('bpmndi:BPMNDiagram/bpmndi:BPMNPlane',NS)) for t in docs)
    all_bounds=plane.xpath('.//dc:Bounds',namespaces=NS)
    width=max(float(b.get('x'))+float(b.get('width')) for b in all_bounds)
    height=max(float(b.get('y'))+float(b.get('height')) for b in all_bounds)
    assert width<10000 and width*height<36000000,'El lienzo excede el límite documentado de Bizagi.'
    output=BASE/'SIG_Electrocentro_Completo.bpmn'
    result.write(str(output),encoding='UTF-8',xml_declaration=True,pretty_print=True)
    report={'archivo':output.name,'procesos':14,'pools':14,'lienzos':1,'formato':'BPMN 2.0 XML con BPMNDI',
        'xsd_omg':'VALIDO','ids_y_referencias':'VALIDOS','imagenes_incrustadas':False,
        'ancho_lienzo':width,'alto_lienzo':height,'superficie':width*height,
        'sha256':hashlib.sha256(output.read_bytes()).hexdigest(),
        'importacion_nativa_bizagi':'No ejecutada en este entorno'}
    (BASE/'validacion_archivo_completo.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
    print(json.dumps(report,ensure_ascii=False))

if __name__=='__main__':main()
