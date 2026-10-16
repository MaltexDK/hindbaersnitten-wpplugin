(function(){
  function sortTable(tbody, key, type, dir){
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const get = (tr)=>{
      const v = tr.dataset[key] || '';
      if (type === 'num')  return parseFloat(v) || 0;
      if (type === 'date') return Date.parse(v) || 0;
      return v.toString().toLowerCase();
    };
    rows.sort((a,b)=>{
      const va = get(a), vb = get(b);
      if (va < vb) return dir==='asc' ? -1 : 1;
      if (va > vb) return dir==='asc' ?  1 : -1;
      return 0;
    });
    rows.forEach(r => tbody.appendChild(r));
  }

  async function fetchJSON(url){
    const r = await fetch(url, {credentials:'omit'});
    if (!r.ok) throw new Error('HTTP '+r.status);
    return await r.json();
  }

  function renderDetailHTML(r){
    const krymmel = (n)=>{
      n = Math.max(0, Math.min(6, n|0));
      let s = '<span class="hs-krymmel">';
      for(let i=0;i<n;i++) s+='<i class="krym"></i>';
      for(let i=n;i<6;i++) s+='<i class="krym off"></i>';
      return s+'</span>';
    };
    const date = r.review_date ? new Date(r.review_date+'T00:00:00') : null;
    const dstr = date ? date.toLocaleDateString() : '';
    const by = (dstr && r.author) ? `Anmeldt den ${dstr} af ${r.author}` : '';
    const addr = `${r.bakery_address} • ${r.bakery_postcode} ${r.bakery_city}`.replace(/^ • /,'');
    const body = r.body || '';

    return `
      <div class="hs-card hs-detail">
        <div class="hs-detail-head">
          <h2>${r.title||''}</h2>
          <div>${krymmel(r.rating||0)}</div>
          ${by?`<div class="hs-detail-meta">${by}</div>`:''}
        </div>
        <div class="hs-detail-bakery"><strong>${r.bakery_name||''}</strong><br>${addr}</div>
        <div class="hs-detail-body">${body}</div>
        ${r.image_url?`<div class="hs-detail-image"><img src="${r.image_url}" alt="${r.image_alt||''}">${r.image_alt?`<div class="cap">${r.image_alt}</div>`:''}</div>`:''}
      </div>`;
  }

  function hydrateList(container){
    const table = container.querySelector('.hs-table');
    const tbody = table.querySelector('tbody');

    // Klik-sortering
    let state = {key:'review_date', dir:'desc'};
    table.querySelectorAll('.hs-sort').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const key  = btn.dataset.key;
        const type = btn.dataset.type || 'text';
        const dir  = (state.key===key && state.dir==='asc') ? 'desc' : 'asc';
        state = {key, dir};
        sortTable(tbody, key, type, dir);
        table.querySelectorAll('.hs-sort .caret').forEach(c => c.textContent='↕');
        btn.querySelector('.caret').textContent = dir==='asc' ? '▲' : '▼';
      });
    });

    // Åbn detalje inline
    const base = (window.HSNITTE_API && HSNITTE_API.base) || '';
    const rows = Array.from(tbody.querySelectorAll('tr.hs-row'));
    rows.forEach(tr=>{
      const link = tr.querySelector('a.hs-open');
      link.addEventListener('click', async (ev)=>{
        ev.preventDefault();
        const id = tr.dataset.id;
        const detail = container.querySelector('.hs-detail');
        detail.style.display='block';
        detail.innerHTML = '<div class="hs-card">Henter…</div>';
        try{
          const data = await fetchJSON(`${base}/reviews/${id}`);
          detail.innerHTML = renderDetailHTML(data);
          detail.scrollIntoView({behavior:'smooth', block:'start'});
        }catch(e){
          detail.innerHTML = `<div class="hs-error">Kunne ikke hente anmeldelsen.</div>`;
        }
      });
    });
  }

  function hydrateLatest(card){
    const base = (window.HSNITTE_API && HSNITTE_API.base) || '';
    const id = card.getAttribute('data-id');
    const link = card.querySelector('.hs-latest-title a.hs-open');
    const box  = card.querySelector('.hs-detail');
    link.addEventListener('click', async (e)=>{
      e.preventDefault();
      box.style.display='block';
      box.innerHTML = '<div class="hs-card">Henter…</div>';
      try{
        const data = await fetchJSON(`${base}/reviews/${id}`);
        box.innerHTML = renderDetailHTML(data, false);
      }catch(e){
        box.innerHTML = '<div class="hs-error">Kunne ikke hente anmeldelsen.</div>';
      }
    });
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.hs-wrap[data-hs-view="list"]').forEach(hydrateList);
    document.querySelectorAll('.hs-card.hs-latest').forEach(hydrateLatest);
  });
})();
