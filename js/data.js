/* JojoKicks — Mock Data
   球鞋商品、分类、博客文章
   emoji 作为占位图（实际项目替换为真实图片）
*/
window.JK_DATA = {
  brands: [
    { id: 'nike', name: 'NIKE' },
    { id: 'jordan', name: 'JORDAN' },
    { id: 'adidas', name: 'ADIDAS' },
    { id: 'yeezy', name: 'YEEZY' },
    { id: 'newbalance', name: 'NEW BALANCE' },
    { id: 'asics', name: 'ASICS' },
    { id: 'puma', name: 'PUMA' },
    { id: 'converse', name: 'CONVERSE' },
    { id: 'vans', name: 'VANS' },
  ],
  categories: [
    { id: 'jordan', name: 'JORDAN', desc: 'Iconic Jumpman — limited drops', count: 124, emoji: '👟', big: true, gradient: 'linear-gradient(135deg, #c8102e, #5a0a16)' },
    { id: 'running', name: 'RUNNING', desc: 'Speed & cushioning tech', count: 86, emoji: '🏃' },
    { id: 'lifestyle', name: 'LIFESTYLE', desc: 'Everyday heat', count: 142, emoji: '👜' },
    { id: 'basketball', name: 'BASKETBALL', desc: 'Court-ready performance', count: 64, emoji: '🏀' },
    { id: 'skate', name: 'SKATE', desc: 'Built for board feel', count: 48, emoji: '🛹' },
  ],
  // 12 双球鞋 emoji 占位；真实环境换图为 /assets/images/*.jpg
  products: [
    { id: 1, brand: 'Jordan', name: 'Air Jordan 1 Retro High OG "Bred"', price: 320, was: 380, tag: 'hot', rating: 4.9, reviews: 234, emoji: '👟', color: '#c8102e', colors: ['#c8102e','#000','#fff'], sizes: [7,7.5,8,8.5,9,9.5,10,10.5,11,12] },
    { id: 2, brand: 'Nike', name: 'Dunk Low Retro "Panda"', price: 145, was: 165, tag: 'new', rating: 4.8, reviews: 412, emoji: '👟', color: '#fff', colors: ['#fff','#000','#d4af37'], sizes: [6,7,8,9,10,11,12,13] },
    { id: 3, brand: 'Yeezy', name: 'Yeezy Boost 350 V2 "Zebra"', price: 280, was: 320, tag: 'sale', rating: 4.7, reviews: 187, emoji: '👟', color: '#f5f5f5', colors: ['#f5f5f5','#000'], sizes: [7,8,9,10,11,12] },
    { id: 4, brand: 'Nike', name: 'Air Force 1 Low "07"', price: 130, was: 130, rating: 4.9, reviews: 982, emoji: '👟', color: '#fff', colors: ['#fff','#000','#c8102e'], sizes: [6,7,8,9,10,11,12,13,14] },
    { id: 5, brand: 'Adidas', name: 'Yeezy Foam Runner "Onyx"', price: 95, was: 110, tag: 'sale', rating: 4.6, reviews: 156, emoji: '🥿', color: '#222', colors: ['#222','#c8102e'], sizes: [7,8,9,10,11,12] },
    { id: 6, brand: 'Jordan', name: 'Air Jordan 4 Retro "Military Black"', price: 295, was: 295, tag: 'new', rating: 4.8, reviews: 198, emoji: '👟', color: '#000', colors: ['#000','#fff'], sizes: [7,8,9,10,11,12,13] },
    { id: 7, brand: 'New Balance', name: '550 "White Green"', price: 130, was: 130, rating: 4.7, reviews: 312, emoji: '👟', color: '#fff', colors: ['#fff','#000','#2ecc71'], sizes: [7,8,9,10,11,12] },
    { id: 8, brand: 'Nike', name: 'Air Max 1 "Patta Waves Noise Aqua"', price: 220, was: 240, tag: 'hot', rating: 4.9, reviews: 142, emoji: '👟', color: '#2dd4bf', colors: ['#2dd4bf','#000'], sizes: [7,8,9,10,11] },
    { id: 9, brand: 'Asics', name: 'GEL-1130 "Cream Black"', price: 110, was: 130, tag: 'sale', rating: 4.5, reviews: 88, emoji: '👟', color: '#f0e4d0', colors: ['#f0e4d0','#000','#c8102e'], sizes: [7,8,9,10,11,12] },
    { id: 10, brand: 'Adidas', name: 'Samba OG "Cloud White Core Black"', price: 130, was: 130, rating: 4.8, reviews: 421, emoji: '👟', color: '#fff', colors: ['#fff','#000','#d4af37'], sizes: [6,7,8,9,10,11,12] },
    { id: 11, brand: 'Nike', name: 'Vomero 5 "Photon Dust Metallic Silver"', price: 150, was: 170, tag: 'new', rating: 4.6, reviews: 96, emoji: '👟', color: '#d4d4d4', colors: ['#d4d4d4','#000'], sizes: [7,8,9,10,11,12] },
    { id: 12, brand: 'Converse', name: 'Chuck 70 Hi "Black Egret"', price: 85, was: 85, rating: 4.7, reviews: 254, emoji: '👟', color: '#000', colors: ['#000','#fff','#c8102e'], sizes: [6,7,8,9,10,11,12] },
  ],
  // 列表页用，全部 24 个
  listProducts: [], // 填充
  blog: [
    { id: 1, title: 'Top 10 Sneaker Drops of 2026', date: '2026-07-08', cat: 'Drops', excerpt: 'From Air Jordan retros to fresh Yeezy colorways — these are the kicks defining the year.', emoji: '👟' },
    { id: 2, title: 'How to Spot Fake Sneakers: A 2026 Guide', date: '2026-07-02', cat: 'Guides', excerpt: 'Counterfeits are getting smarter. Here is how to authenticate the most counterfeited pairs.', emoji: '🔍' },
    { id: 3, title: 'The Comeback of the Asics GEL-1130', date: '2026-06-25', cat: 'Trends', excerpt: 'Why the once-overlooked GEL series is now the most-wanted lifestyle silhouette.', emoji: '🥇' },
    { id: 4, title: 'Inside JojoKicks: Sourcing the Heat', date: '2026-06-18', cat: 'Behind the Brand', excerpt: 'How our team tracks down deadstock and grail pairs from across the globe.', emoji: '🌍' },
    { id: 5, title: 'Sneaker Care 101: Keep Your Kicks Fresh', date: '2026-06-10', cat: 'Guides', excerpt: 'Storage, cleaning, and rotation — the basics that extend a sneaker\'s life.', emoji: '✨' },
    { id: 6, title: 'New Balance 550 vs 530: Which One Is You?', date: '2026-06-01', cat: 'Comparisons', excerpt: 'Two icons, two vibes. We break down fit, feel, and styling.', emoji: '⚖️' },
  ],
  faq: [
    { q: 'How long does shipping take?', a: 'Standard shipping arrives in 3–5 business days within the US. Express options are available at checkout. International orders typically arrive in 7–14 business days.' },
    { q: 'Are your sneakers 100% authentic?', a: 'Yes. Every pair on JojoKicks goes through a multi-step authentication process by our in-house team of sneaker authenticators. We stand behind every order with a money-back authenticity guarantee.' },
    { q: 'What is your return policy?', a: 'We accept returns within 30 days of delivery for unworn pairs in original packaging. Final sale items are clearly marked. Refunds are processed within 5 business days of receipt.' },
    { q: 'Do you offer same-day delivery?', a: 'Yes, in select metro areas (NYC, LA, Miami, Chicago, Houston). Order before 2pm local time to qualify.' },
    { q: 'How does the JojoClub membership work?', a: 'JojoClub members get early access to drops, exclusive colorways, free standard shipping, and 2x points on every purchase. There are three tiers — Bronze, Gold, and Platinum.' },
    { q: 'Can I sell my collection on JojoKicks?', a: 'Yes. Our consignment program is open to anyone. Send us a list of your pairs, we will quote within 48 hours, and once accepted you ship them to us for free.' },
  ],
};

// 复制前 12 个，再补 12 个变体做满 24
window.JK_DATA.listProducts = [
  ...window.JK_DATA.products,
  ...window.JK_DATA.products.map(p => ({...p, id: p.id + 100, name: p.name.replace(/Retro|Low|OG|Hi/,'').trim() + ' (Alternate)', price: p.price + 20, was: p.was + 20})),
];
