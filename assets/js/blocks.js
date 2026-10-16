(function(wp){
  const { registerBlockType } = wp.blocks;
  const { useState, useEffect } = wp.element;
  const { __ } = wp.i18n;
  const { PanelBody, SelectControl, RangeControl, TextControl, Notice } = wp.components;
  const { InspectorControls } = wp.blockEditor;

  const apiBase = (w=> w.HSNITTE_API && w.HSNITTE_API.base || '')(window);
  const settingsUrl = (w=> w.HSNITTE_API && w.HSNITTE_API.settingsUrl || '#')(window);

  async function fetchJSON(url){
    const r = await fetch(url, { credentials:'omit' });
    if (!r.ok) throw new Error('HTTP '+r.status);
    return await r.json();
  }

  const PreviewList = ({sort,dir,per_page,q})=>{
    const [data,setData] = useState(null);
    const [err,setErr] = useState('');

    useEffect(()=>{
      let alive = true;
      setData(null); setErr('');
      if (!apiBase) return;
      const u = new URL(apiBase+'/reviews');
      u.searchParams.set('sort', sort||'date');
      u.searchParams.set('dir',  dir||'desc');
      u.searchParams.set('per_page', per_page||20);
      if (q) u.searchParams.set('q', q);
      fetchJSON(u.toString()).then(j=>{ if(alive) setData(j); }).catch(e=>{ if(alive) setErr(e.message); });
      return ()=>{ alive=false; };
    }, [sort,dir,per_page,q]);

    if (!apiBase) return wp.element.createElement(Notice, {status:'warning', isDismissible:false},
      wp.element.createElement('span', null, __('Sæt API-URL under Indstillinger → Hindbærsnitte API.', 'hindbaersnitte')),
      wp.element.createElement('a', {href:settingsUrl, style:{marginLeft:8}}, __('Åbn indstillinger', 'hindbaersnitte'))
    );
    if (err) return wp.element.createElement('div', {className:'hs-error'}, 'Fejl: '+err);
    if (!data) return wp.element.createElement('div', {className:'hs-card'}, 'Henter…');

    const rows = (data.data||[]);
    return wp.element.createElement('div', {className:'hs-wrap', 'data-hs-view':'list'},
      wp.element.createElement('div', {className:'hs-card'},
        wp.element.createElement('h2', null, __('Anmeldelser','hindbaersnitte')),
        wp.element.createElement('table', {className:'hs-table'},
          wp.element.createElement('thead', null,
            wp.element.createElement('tr', null,
              wp.element.createElement('th', null, __('Overskrift','hindbaersnitte')),
              wp.element.createElement('th', null, __('Krymmel','hindbaersnitte')),
              wp.element.createElement('th', null, __('Bageri','hindbaersnitte')),
              wp.element.createElement('th', null, __('Dato','hindbaersnitte')),
              wp.element.createElement('th', null, __('Anmelder','hindbaersnitte')),
            )
          ),
          wp.element.createElement('tbody', null,
            rows.slice(0,10).map(r=> wp.element.createElement('tr', {key:r.id},
              wp.element.createElement('td', null, r.title),
              wp.element.createElement('td', null, '★'.repeat(r.rating||0)),
              wp.element.createElement('td', null, r.bakery_name||''),
              wp.element.createElement('td', null, r.review_date||''),
              wp.element.createElement('td', null, r.author||'')
            ))
          )
        )
      )
    );
  };

  // Use the PHP renderer so editor and published full reviews stay in sync.
  const ServerSideRender = wp.serverSideRender.default || wp.serverSideRender;
  const PreviewSingle = ({id}) => wp.element.createElement(ServerSideRender, {
    block: 'hindbaersnitte/review', attributes: {id: id || 0}
  });
  const PreviewLatest = () => wp.element.createElement(ServerSideRender, {
    block: 'hindbaersnitte/latest'
  });

  // 1) Listeblok
  registerBlockType('hindbaersnitte/reviews', {
    title: __('Snitter – Liste', 'hindbaersnitte'),
    icon: 'list-view',
    category: 'widgets',
    attributes: {
      sort:     {type:'string', default:'date'},
      dir:      {type:'string', default:'desc'},
      per_page: {type:'number', default:20},
      q:        {type:'string', default:''},
    },
    edit: (props)=>{
      const { attributes, setAttributes } = props;
      return [
        wp.element.createElement(InspectorControls, null,
          wp.element.createElement(PanelBody, {title: __('Indstillinger', 'hindbaersnitte'), initialOpen:true},
            wp.element.createElement(SelectControl, {
              label: __('Sortér efter','hindbaersnitte'),
              value: attributes.sort,
              options: [
                {label:__('Dato','hindbaersnitte'), value:'date'},
                {label:__('Krymmel','hindbaersnitte'), value:'rating'},
                {label:__('Overskrift','hindbaersnitte'), value:'title'},
                {label:__('Anmelder','hindbaersnitte'), value:'author'},
              ],
              onChange:(v)=>setAttributes({sort:v})
            }),
            wp.element.createElement(SelectControl, {
              label: __('Retning','hindbaersnitte'),
              value: attributes.dir,
              options: [
                {label:__('Faldende','hindbaersnitte'), value:'desc'},
                {label:__('Stigende','hindbaersnitte'), value:'asc'},
              ],
              onChange:(v)=>setAttributes({dir:v})
            }),
            wp.element.createElement(RangeControl, {
              label: __('Antal pr. side (API)','hindbaersnitte'),
              value: attributes.per_page,
              min:1, max:100,
              onChange:(v)=>setAttributes({per_page:v})
            }),
            wp.element.createElement(TextControl, {
              label: __('Søg (q)','hindbaersnitte'),
              value: attributes.q,
              onChange:(v)=>setAttributes({q:v})
            })
          )
        ),
        wp.element.createElement(PreviewList, attributes)
      ];
    },
    save: ()=>null // render server-side
  });

  // 2) Enkelt anmeldelse
  registerBlockType('hindbaersnitte/review', {
    title: __('Snitter – Anmeldelse', 'hindbaersnitte'),
    icon: 'media-document',
    category: 'widgets',
    attributes: { id: {type:'number', default:0} },
    edit: (props)=>{
      const { attributes, setAttributes } = props;
      return [
        wp.element.createElement(InspectorControls, null,
          wp.element.createElement(PanelBody, {title: __('Indstillinger', 'hindbaersnitte'), initialOpen:true},
            wp.element.createElement(TextControl, {
              label: __('Anmeldelses-ID','hindbaersnitte'),
              type:'number',
              value: attributes.id || '',
              onChange:(v)=>setAttributes({id: parseInt(v||'0',10)||0})
            })
          )
        ),
        wp.element.createElement(PreviewSingle, {id: attributes.id})
      ];
    },
    save: ()=>null
  });

  // 3) Nyeste
  registerBlockType('hindbaersnitte/latest', {
    title: __('Snitter – Nyeste', 'hindbaersnitte'),
    icon: 'star-filled',
    category: 'widgets',
    edit: ()=> wp.element.createElement(PreviewLatest, null),
    save: ()=>null
  });

})(window.wp);
