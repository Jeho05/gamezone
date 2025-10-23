# 🚀 Guide Rapide - Moderniser une Page

## Template de Base

```jsx
'use client';
import { useState } from 'react';
import { Icon1, Icon2 } from 'lucide-react';
import VideoBackground from '@/components/ui/VideoBackground';
import FloatingObjects from '@/components/ui/FloatingObjects';
import GlassCard from '@/components/ui/GlassCard';
import NeonText from '@/components/ui/NeonText';
import ParallaxObject from '@/components/ui/ParallaxObject';

export default function MyPage() {
  return (
    <VideoBackground 
      videoSrc="/images/video/Cyber_Arcade_Neon_Ember.mp4"
      overlayOpacity={0.75}
    >
      <div className="min-h-screen relative">
        {/* Objets flottants globaux */}
        <FloatingObjects count={6} opacity={0.12} />
        
        {/* Objets parallaxe décoratifs */}
        <ParallaxObject 
          src="/images/objet/Goku-Blue-PNG-Photo.png" 
          alt="Goku" 
          size={120} 
          speed={0.4} 
          position={{ x: 10, y: 20 }} 
        />
        
        {/* Contenu principal */}
        <div className="container mx-auto px-6 py-24 relative z-10">
          {/* Header avec NeonText */}
          <div className="text-center mb-16 animate-slide-in-up">
            <NeonText color="purple" className="text-5xl md:text-6xl mb-4">
              Mon Titre
            </NeonText>
            <p className="text-gray-300 text-lg">Ma description</p>
          </div>

          {/* Cards avec GlassCard */}
          <div className="grid md:grid-cols-3 gap-6">
            <GlassCard className="p-6 animate-scale-in">
              <h3 className="text-2xl font-bold gradient-text mb-3">
                Titre Card
              </h3>
              <p className="text-gray-300">Contenu</p>
            </GlassCard>
          </div>
        </div>
      </div>
    </VideoBackground>
  );
}
```

---

## Checklist pour Chaque Page

### 1. Imports de base
```jsx
'use client';
import VideoBackground from '@/components/ui/VideoBackground';
import FloatingObjects from '@/components/ui/FloatingObjects';
import GlassCard from '@/components/ui/GlassCard';
import NeonText from '@/components/ui/NeonText';
import ParallaxObject from '@/components/ui/ParallaxObject';
```

### 2. Structure VideoBackground
```jsx
<VideoBackground videoSrc="/images/video/[CHOISIR].mp4" overlayOpacity={0.75}>
  {/* Contenu */}
</VideoBackground>
```

### 3. Ajouter objets flottants
```jsx
<FloatingObjects count={6} opacity={0.12} />
```

### 4. Ajouter 2-4 objets parallaxe
```jsx
<ParallaxObject src="/images/objet/[IMAGE].png" alt="" size={100} speed={0.4} position={{ x: 10, y: 20 }} />
```

### 5. Convertir les titres
```jsx
<NeonText color="purple" className="text-5xl">Titre</NeonText>
```

### 6. Convertir les cards
```jsx
<GlassCard className="p-6">Contenu</GlassCard>
```

### 7. Ajouter animations
```jsx
<div className="animate-slide-in-up">...</div>
<div className="animate-scale-in" style={{ animationDelay: '0.1s' }}>...</div>
```

### 8. Moderniser les boutons
```jsx
<button className="group relative overflow-hidden bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-8 py-4 rounded-xl font-bold transition-all duration-300 hover-lift neon-border-purple">
  <div className="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" />
  <span className="relative z-10">Mon Bouton</span>
</button>
```

---

## Choix de Vidéo

1. **Arcade_Welcome_Manager_Loop.mp4** (1.4MB)
   - Bon pour: Auth, Admin, Profil
   - Ambiance: Arcade classique

2. **Cyber_Arcade_Neon_Ember.mp4** (1.2MB)
   - Bon pour: Dashboard, Home, Shop
   - Ambiance: Néon cyberpunk

3. **kling_20251010_Image_to_Video.mp4** (13.3MB)
   - Bon pour: Landing, Register
   - Ambiance: Dynamique, futuriste

---

## Choix d'Objets Parallaxe

### Pour pages Gaming générales:
```jsx
<ParallaxObject src="/images/objet/Console-PNG-Clipart.png" ... />
<ParallaxObject src="/images/objet/Dragon-Ball-Z-Logo-PNG-HD.png" ... />
<ParallaxObject src="/images/objet/FIFA-Logo-PNG-Isolated-Image.png" ... />
<ParallaxObject src="/images/objet/—Pngtree—retro neon video game controller_17879972.png" ... />
```

### Pour pages Anime/Action:
```jsx
<ParallaxObject src="/images/objet/Goku-Blue-PNG-Photo.png" ... />
<ParallaxObject src="/images/objet/Naruto-Ashura-Transparent-PNG.png" ... />
<ParallaxObject src="/images/objet/Madara-Transparent-Images-PNG.png" ... />
<ParallaxObject src="/images/objet/Kratos-PNG-Clipart.png" ... />
```

### Pour pages Sport:
```jsx
<ParallaxObject src="/images/objet/FIFA-PNG-Photo.png" ... />
<ParallaxObject src="/images/objet/FIFA-PNG-File.png" ... />
```

---

## Classes CSS Utiles

### Backgrounds
```css
.particles-bg          /* Fond avec particules */
.glass                 /* Glass morphism léger */
.glass-strong          /* Glass morphism intense */
```

### Bordures Néon
```css
.neon-border-purple
.neon-border-pink
.neon-border-blue
```

### Animations
```css
.animate-slide-in-up
.animate-slide-in-right
.animate-scale-in
.animate-float
.animate-glow-pulse
.animate-bounce-soft
.hover-lift
```

### Texte
```css
.gradient-text         /* Gradient violet/rose/bleu */
```

---

## Exemple Complet: Dashboard

```jsx
'use client';
import { Trophy, Star, Zap, Clock } from 'lucide-react';
import VideoBackground from '@/components/ui/VideoBackground';
import FloatingObjects from '@/components/ui/FloatingObjects';
import GlassCard from '@/components/ui/GlassCard';
import NeonText from '@/components/ui/NeonText';
import ParallaxObject from '@/components/ui/ParallaxObject';

export default function Dashboard() {
  const stats = [
    { icon: Trophy, label: 'Points', value: '12,500', color: 'text-yellow-400' },
    { icon: Star, label: 'Niveau', value: '42', color: 'text-purple-400' },
    { icon: Zap, label: 'Sessions', value: '156', color: 'text-blue-400' },
    { icon: Clock, label: 'Temps', value: '89h', color: 'text-pink-400' },
  ];

  return (
    <VideoBackground 
      videoSrc="/images/video/Cyber_Arcade_Neon_Ember.mp4"
      overlayOpacity={0.7}
    >
      <div className="min-h-screen relative overflow-hidden">
        <FloatingObjects count={8} opacity={0.1} />
        
        <ParallaxObject src="/images/objet/Goku-Blue-PNG-Photo.png" alt="Goku" size={150} speed={0.3} position={{ x: 5, y: 10 }} />
        <ParallaxObject src="/images/objet/Kratos-PNG-Clipart.png" alt="Kratos" size={120} speed={0.5} position={{ x: 90, y: 15 }} rotate />
        <ParallaxObject src="/images/objet/Console-PNG-Clipart.png" alt="Console" size={100} speed={0.4} position={{ x: 10, y: 80 }} />
        
        <div className="container mx-auto px-6 py-24 relative z-10">
          {/* Header */}
          <div className="text-center mb-16 animate-slide-in-up">
            <NeonText color="purple" className="text-6xl md:text-7xl mb-4">
              Tableau de Bord
            </NeonText>
            <p className="text-gray-300 text-xl">Bienvenue, Champion!</p>
          </div>

          {/* Stats Grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
            {stats.map((stat, index) => (
              <div
                key={index}
                className="animate-scale-in"
                style={{ animationDelay: `${index * 0.1}s` }}
              >
                <GlassCard className="p-6 text-center hover-lift">
                  <stat.icon className={`w-12 h-12 mx-auto mb-4 ${stat.color} animate-glow-pulse`} />
                  <div className="text-4xl font-bold gradient-text mb-2">{stat.value}</div>
                  <div className="text-gray-300">{stat.label}</div>
                </GlassCard>
              </div>
            ))}
          </div>

          {/* Content */}
          <GlassCard className="p-8 animate-slide-in-up" style={{ animationDelay: '0.4s' }}>
            <h2 className="text-3xl font-bold gradient-text mb-6">Activité Récente</h2>
            {/* Votre contenu ici */}
          </GlassCard>
        </div>
      </div>
    </VideoBackground>
  );
}
```

---

## Tips & Tricks

### 1. Performance
- Limiter objets flottants à 6-8
- Utiliser opacity 0.1-0.15 pour les objets
- Compresser les vidéos (max 2MB recommandé)

### 2. Responsive
- Toujours tester sur mobile
- Utiliser `md:` et `lg:` breakpoints
- Réduire taille texte sur mobile (`text-4xl md:text-6xl`)

### 3. Accessibilité
- Garder bon contraste texte/background
- Ajouter `alt` aux images
- Tester navigation clavier

### 4. Cohérence
- Utiliser même palette de couleurs
- Espacements cohérents (multiples de 4)
- Même style de boutons partout

---

## Commandes Rapides

### Copier un objet parallaxe:
```jsx
<ParallaxObject src="/images/objet/[NOM].png" alt="[ALT]" size={[TAILLE]} speed={[0.3-0.6]} position={{ x: [0-100], y: [0-100] }} />
```

### Copier un NeonText:
```jsx
<NeonText color="[purple|pink|blue]" className="text-[SIZE]">[TEXTE]</NeonText>
```

### Copier un GlassCard:
```jsx
<GlassCard className="p-[4-8]">
  [CONTENU]
</GlassCard>
```

---

**Bon design! 🎨🎮**
