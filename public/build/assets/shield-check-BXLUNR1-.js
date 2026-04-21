import{I as l}from"./app-ByQSj395.js";/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const C=e=>{for(const t in e)if(t.startsWith("aria-")||t==="role"||t==="title")return!0;return!1};/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=e=>e==="";/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=(...e)=>e.filter((t,c,r)=>!!t&&t.trim()!==""&&r.indexOf(t)===c).join(" ").trim();/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=e=>e.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase();/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const f=e=>e.replace(/^([A-Z])|[\s-_]+(\w)/g,(t,c,r)=>r?r.toUpperCase():c.toLowerCase());/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const g=e=>{const t=f(e);return t.charAt(0).toUpperCase()+t.slice(1)};/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */var o={xmlns:"http://www.w3.org/2000/svg",width:24,height:24,viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":2,"stroke-linecap":"round","stroke-linejoin":"round"};/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=({name:e,iconNode:t,absoluteStrokeWidth:c,"absolute-stroke-width":r,strokeWidth:a,"stroke-width":d,size:i=o.width,color:w=o.stroke,...s},{slots:n})=>l("svg",{...o,...s,width:i,height:i,stroke:w,"stroke-width":h(c)||h(r)||c===!0||r===!0?Number(a||d||o["stroke-width"])*24/Number(i):a||d||o["stroke-width"],class:p("lucide",s.class,...e?[`lucide-${u(g(e))}-icon`,`lucide-${u(e)}`]:["lucide-icon"]),...!n.default&&!C(s)&&{"aria-hidden":"true"}},[...t.map(k=>l(...k)),...n.default?[n.default()]:[]]);/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const m=(e,t)=>(c,{slots:r,attrs:a})=>l(y,{...a,...c,iconNode:t,name:e},r);/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=m("circle-check",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]]);/**
 * @license lucide-vue-next v0.563.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=m("shield-check",[["path",{d:"M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z",key:"oel41y"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]]);export{A as C,v as S,m as c};
