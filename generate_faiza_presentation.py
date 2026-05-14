"""
Générateur de présentation commerciale premium — FAIZA MULTISERVICE
16 slides · PowerPoint · Direction artistique haut de gamme
"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.util import Inches, Pt
import copy
from lxml import etree

# ─── PALETTE DE COULEURS ────────────────────────────────────────────────────
BLEU_NUIT    = RGBColor(0x1A, 0x1F, 0x2E)   # Fond principal
ANTHRACITE   = RGBColor(0x25, 0x28, 0x30)   # Fond secondaire
OR_DOUX      = RGBColor(0xC9, 0xA9, 0x6E)   # Accent doré
BLANC_CASSE  = RGBColor(0xF5, 0xF0, 0xE8)   # Texte principal
GRIS_PIERRE  = RGBColor(0xAA, 0xA8, 0xA4)   # Texte secondaire
BEIGE_CHAMP  = RGBColor(0xED, 0xE8, 0xDF)   # Fond carte/encart
ARGENT       = RGBColor(0x8E, 0x8E, 0x8E)   # Séparateurs
BLANC_PUR    = RGBColor(0xFF, 0xFF, 0xFF)
NOIR         = RGBColor(0x00, 0x00, 0x00)
OR_FONCE     = RGBColor(0xA0, 0x80, 0x45)   # Doré plus foncé pour contraste

# ─── DIMENSIONS WIDESCREEN 16:9 ─────────────────────────────────────────────
W = Inches(13.333)
H = Inches(7.5)

prs = Presentation()
prs.slide_width  = W
prs.slide_height = H

# ─── HELPERS ────────────────────────────────────────────────────────────────

def blank_slide(prs):
    blank_layout = prs.slide_layouts[6]
    return prs.slides.add_slide(blank_layout)

def add_rect(slide, x, y, w, h, fill_color=None, line_color=None, line_width=Pt(0)):
    shape = slide.shapes.add_shape(1, x, y, w, h)
    shape.line.width = line_width
    if fill_color:
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill_color
    else:
        shape.fill.background()
    if line_color:
        shape.line.color.rgb = line_color
        shape.line.width = line_width
    else:
        shape.line.fill.background()
    return shape

def add_textbox(slide, text, x, y, w, h,
                font_name="Garamond", font_size=Pt(12),
                bold=False, italic=False,
                color=BLANC_CASSE, align=PP_ALIGN.LEFT,
                word_wrap=True, space_before=Pt(0), space_after=Pt(0),
                line_spacing=None, char_spacing=0):
    txb = slide.shapes.add_textbox(x, y, w, h)
    txb.word_wrap = word_wrap
    tf = txb.text_frame
    tf.word_wrap = word_wrap
    p = tf.paragraphs[0]
    p.alignment = align
    if space_before:
        p.space_before = space_before
    if space_after:
        p.space_after = space_after
    if line_spacing:
        p.line_spacing = line_spacing
    run = p.add_run()
    run.text = text
    run.font.name = font_name
    run.font.size = font_size
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = color
    if char_spacing:
        rPr = run._r.get_or_add_rPr()
        rPr.set('spc', str(char_spacing))
    return txb

def add_line(slide, x1, y1, x2, y2, color=OR_DOUX, width=Pt(0.75)):
    connector = slide.shapes.add_connector(1, x1, y1, x2, y2)
    connector.line.color.rgb = color
    connector.line.width = width
    return connector

def set_slide_bg(slide, color):
    background = slide.background
    fill = background.fill
    fill.solid()
    fill.fore_color.rgb = color

def add_multi_para_textbox(slide, paragraphs_data, x, y, w, h, word_wrap=True):
    """
    paragraphs_data: list of dicts with keys:
      text, font_name, font_size, bold, italic, color, align,
      space_before, space_after, line_spacing, char_spacing
    """
    txb = slide.shapes.add_textbox(x, y, w, h)
    txb.word_wrap = word_wrap
    tf = txb.text_frame
    tf.word_wrap = word_wrap

    first = True
    for pd in paragraphs_data:
        if first:
            p = tf.paragraphs[0]
            first = False
        else:
            p = tf.add_paragraph()
        p.alignment = pd.get('align', PP_ALIGN.LEFT)
        if pd.get('space_before'):
            p.space_before = pd['space_before']
        if pd.get('space_after'):
            p.space_after = pd['space_after']
        if pd.get('line_spacing'):
            p.line_spacing = pd['line_spacing']
        run = p.add_run()
        run.text = pd.get('text', '')
        run.font.name = pd.get('font_name', 'Garamond')
        run.font.size = pd.get('font_size', Pt(12))
        run.font.bold = pd.get('bold', False)
        run.font.italic = pd.get('italic', False)
        run.font.color.rgb = pd.get('color', BLANC_CASSE)
        cs = pd.get('char_spacing', 0)
        if cs:
            rPr = run._r.get_or_add_rPr()
            rPr.set('spc', str(cs))
    return txb

def add_pattern_overlay(slide, opacity_pct=8):
    """Ajoute un motif de fond subtil via rectangles répétés."""
    stripe_w = Inches(0.05)
    gap = Inches(0.35)
    x = Inches(0)
    while x < W:
        s = add_rect(slide, x, Inches(0), stripe_w, H, fill_color=ARGENT)
        # opacity via XML
        spPr = s.shape._element.find('.//{http://schemas.openxmlformats.org/drawingml/2006/main}solidFill')
        if spPr is not None:
            alpha_el = etree.SubElement(
                spPr,
                '{http://schemas.openxmlformats.org/drawingml/2006/main}alpha'
            )
            alpha_el.set('val', str(opacity_pct * 1000))
        x += gap

def add_gold_bar(slide, y_pos, full_width=True):
    """Filet doré horizontal."""
    if full_width:
        add_rect(slide, Inches(0), y_pos, W, Inches(0.025), fill_color=OR_DOUX)
    else:
        add_rect(slide, Inches(0.6), y_pos, Inches(3), Inches(0.025), fill_color=OR_DOUX)

def add_slide_number_tag(slide, num, total=16):
    add_textbox(slide, f"{num:02d} / {total}",
                W - Inches(1.2), H - Inches(0.45),
                Inches(1.0), Inches(0.35),
                font_name="Montserrat", font_size=Pt(7),
                color=ARGENT, align=PP_ALIGN.RIGHT)

def add_logo_mark(slide, x=Inches(0.45), y=Inches(0.22), size=Pt(16)):
    add_textbox(slide, "KF",
                x, y, Inches(1.0), Inches(0.5),
                font_name="Garamond", font_size=size,
                bold=True, color=OR_DOUX, align=PP_ALIGN.LEFT, char_spacing=200)


# ════════════════════════════════════════════════════════════════════════════
# SLIDE 1 — COUVERTURE
# ════════════════════════════════════════════════════════════════════════════
def slide_01():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    # Bandes décoratives ondulées simulées par rectangles obliques subtils
    for i in range(0, 28):
        xi = Inches(i * 0.5 - 1)
        add_rect(sl, xi, Inches(0), Inches(0.03), H,
                 fill_color=RGBColor(0x2A, 0x30, 0x42))

    # Filets dorés haut et bas
    add_gold_bar(sl, Inches(0.55))
    add_gold_bar(sl, H - Inches(0.58))

    # Bloc central
    cx = Inches(3.5)
    cy = Inches(2.2)
    cw = Inches(6.3)

    # Monogramme
    add_textbox(sl, "KF",
                cx + Inches(2.4), cy, Inches(1.5), Inches(0.9),
                font_name="Garamond", font_size=Pt(52),
                bold=False, color=OR_DOUX, align=PP_ALIGN.CENTER, char_spacing=300)

    # Séparateur sous monogramme
    add_rect(sl, cx + Inches(2.2), cy + Inches(0.85), Inches(1.9), Inches(0.018),
             fill_color=ARGENT)

    # Nom marque
    add_textbox(sl, "FAIZA MULTISERVICE",
                cx, cy + Inches(1.0), cw, Inches(0.55),
                font_name="Montserrat", font_size=Pt(10),
                bold=False, color=BLANC_CASSE, align=PP_ALIGN.CENTER, char_spacing=500)

    add_textbox(sl, "SERVICE CLIENT DE QUALITÉ",
                cx, cy + Inches(1.45), cw, Inches(0.35),
                font_name="Montserrat", font_size=Pt(7),
                color=ARGENT, align=PP_ALIGN.CENTER, char_spacing=400)

    # Filet fin
    add_rect(sl, cx + Inches(1.5), cy + Inches(1.9), Inches(3.3), Inches(0.015),
             fill_color=OR_DOUX)

    # Titre principal
    add_textbox(sl, "CONCIERGERIE &\nSERVICES PRIVÉS EXCLUSIFS",
                cx, cy + Inches(2.05), cw, Inches(1.3),
                font_name="Garamond", font_size=Pt(30),
                bold=False, color=BLANC_CASSE, align=PP_ALIGN.CENTER, char_spacing=100)

    # Baseline
    add_textbox(sl, "Votre partenaire privilégié en services haut de gamme",
                cx, cy + Inches(3.25), cw, Inches(0.45),
                font_name="Garamond", font_size=Pt(13),
                italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    add_slide_number_tag(sl, 1)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 2 — INTRODUCTION
# ════════════════════════════════════════════════════════════════════════════
def slide_02():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    # Bloc gauche (texte) — 55% largeur
    add_rect(sl, Inches(0), Inches(0), Inches(7.3), H, fill_color=BLEU_NUIT)

    # Bloc droit simulé (fond légèrement différent)
    add_rect(sl, Inches(7.3), Inches(0), Inches(6.0), H,
             fill_color=RGBColor(0x22, 0x27, 0x38))

    # Filet vertical doré séparateur
    add_rect(sl, Inches(7.28), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)

    # Filets dorés haut/bas
    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))

    # Logo
    add_logo_mark(sl)

    # Titre
    add_textbox(sl, "L'art du service,",
                Inches(0.7), Inches(1.1), Inches(6.2), Inches(0.65),
                font_name="Garamond", font_size=Pt(32),
                italic=True, color=BLANC_CASSE, align=PP_ALIGN.LEFT)
    add_textbox(sl, "signé Faiza Multiservice.",
                Inches(0.7), Inches(1.7), Inches(6.2), Inches(0.55),
                font_name="Garamond", font_size=Pt(24),
                color=OR_DOUX, align=PP_ALIGN.LEFT)

    # Filet doré sous titre
    add_rect(sl, Inches(0.7), Inches(2.35), Inches(2.8), Inches(0.02), fill_color=OR_DOUX)

    # Corps
    corps = (
        "Chez Faiza Multiservice, chaque rencontre est une promesse tenue.\n"
        "Chaque service, une attention pensée. Chaque client, une relation de confiance."
    )
    add_textbox(sl, corps,
                Inches(0.7), Inches(2.55), Inches(6.1), Inches(1.1),
                font_name="Montserrat", font_size=Pt(11),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT, line_spacing=Pt(18))

    add_textbox(sl,
        "De la garde d'enfants à la conciergerie privée, nous orchestrons\n"
        "votre quotidien avec l'élégance du détail et la chaleur du service humain.",
                Inches(0.7), Inches(3.65), Inches(6.1), Inches(1.0),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    # Signature
    add_rect(sl, Inches(0.7), Inches(4.85), Inches(1.8), Inches(0.018), fill_color=OR_DOUX)
    add_textbox(sl, "— Faiza Kenouf, Directrice",
                Inches(0.7), Inches(5.0), Inches(4.5), Inches(0.4),
                font_name="Garamond", font_size=Pt(11),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    # Bloc droit — texte placeholder (photo zone)
    add_textbox(sl, "ÉQUIPE\nFAIZA MULTISERVICE\n\n"
                    "Personnel sélectionné\nDiscrètion · Élégance · Excellence",
                Inches(7.6), Inches(2.8), Inches(5.3), Inches(2.5),
                font_name="Montserrat", font_size=Pt(10),
                color=ARGENT, align=PP_ALIGN.CENTER, line_spacing=Pt(22))

    add_textbox(sl, "[ Insérer photo équipe palace ]",
                Inches(7.8), Inches(1.1), Inches(4.9), Inches(1.4),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_slide_number_tag(sl, 2)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 3 — VISION & PROMESSE
# ════════════════════════════════════════════════════════════════════════════
def slide_03():
    sl = blank_slide(prs)
    set_slide_bg(sl, ANTHRACITE)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    # Titre
    add_textbox(sl, "L'excellence comme signature.",
                Inches(1.0), Inches(0.9), Inches(11.3), Inches(0.75),
                font_name="Garamond", font_size=Pt(38),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER, char_spacing=50)

    add_textbox(sl, "Notre mission : élever la qualité de vie de chaque client.",
                Inches(2.0), Inches(1.7), Inches(9.3), Inches(0.4),
                font_name="Garamond", font_size=Pt(14),
                italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    # Filet
    add_rect(sl, Inches(5.4), Inches(2.2), Inches(2.5), Inches(0.018), fill_color=OR_DOUX)

    # 4 piliers
    piliers = [
        ("◆", "CONFIANCE", "Une relation solide,\ntransparente et respectueuse"),
        ("◆", "ÉLÉGANCE",  "Chaque détail, pensé\navec soin et discrétion"),
        ("◆", "ENGAGEMENT","Présents, réactifs,\ndisponibles 7j/7"),
        ("◆", "EXPÉRIENCE","Un service adapté,\npersonnalisé, mémorable"),
    ]

    col_w = Inches(2.8)
    start_x = Inches(0.85)
    top_y = Inches(2.55)

    for i, (icon, titre, desc) in enumerate(piliers):
        cx = start_x + i * col_w

        # Fond carte
        add_rect(sl, cx, top_y, Inches(2.55), Inches(3.8),
                 fill_color=RGBColor(0x1A, 0x1F, 0x2E))

        # Icône
        add_textbox(sl, icon,
                    cx + Inches(0.1), top_y + Inches(0.25),
                    Inches(2.35), Inches(0.5),
                    font_name="Garamond", font_size=Pt(18),
                    color=OR_DOUX, align=PP_ALIGN.CENTER)

        # Filet doré sous icône
        add_rect(sl, cx + Inches(0.6), top_y + Inches(0.85),
                 Inches(1.35), Inches(0.018), fill_color=OR_DOUX)

        # Titre pilier
        add_textbox(sl, titre,
                    cx + Inches(0.1), top_y + Inches(1.0),
                    Inches(2.35), Inches(0.45),
                    font_name="Montserrat", font_size=Pt(10),
                    bold=True, color=BLANC_CASSE, align=PP_ALIGN.CENTER, char_spacing=300)

        # Description
        add_textbox(sl, desc,
                    cx + Inches(0.2), top_y + Inches(1.55),
                    Inches(2.15), Inches(1.5),
                    font_name="Garamond", font_size=Pt(11),
                    italic=True, color=GRIS_PIERRE, align=PP_ALIGN.CENTER,
                    line_spacing=Pt(18))

    add_slide_number_tag(sl, 3)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 4 — NOS UNIVERS DE SERVICE
# ════════════════════════════════════════════════════════════════════════════
def slide_04():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    # Titre
    add_textbox(sl, "Des services pensés pour chaque moment de vie.",
                Inches(0.7), Inches(0.75), Inches(8.5), Inches(0.7),
                font_name="Garamond", font_size=Pt(28),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)

    add_textbox(sl, "Une gamme complète pour les familles, les hôtels et les clients privés les plus exigeants.",
                Inches(0.7), Inches(1.52), Inches(8.5), Inches(0.45),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(0.7), Inches(2.08), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    services = [
        ("01", "Baby-sitting"),
        ("02", "Kids Club  —  4 à 9 ans"),
        ("03", "Teen Club  —  10 à 16 ans"),
        ("04", "Assistance à la personne"),
        ("05", "Événementiel privé"),
        ("06", "Conciergerie privée"),
        ("07", "Application de réservation"),
    ]

    col1 = services[:4]
    col2 = services[4:]

    top = Inches(2.3)
    row_h = Inches(0.72)

    for i, (num, name) in enumerate(col1):
        y = top + i * row_h
        add_textbox(sl, num,
                    Inches(0.7), y, Inches(0.7), Inches(0.5),
                    font_name="Garamond", font_size=Pt(20),
                    color=OR_DOUX, align=PP_ALIGN.LEFT)
        add_textbox(sl, name,
                    Inches(1.45), y + Inches(0.05), Inches(4.5), Inches(0.45),
                    font_name="Montserrat", font_size=Pt(12),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        add_rect(sl, Inches(0.7), y + Inches(0.55), Inches(5.5), Inches(0.012),
                 fill_color=RGBColor(0x33, 0x38, 0x4A))

    for i, (num, name) in enumerate(col2):
        y = top + i * row_h
        add_textbox(sl, num,
                    Inches(6.8), y, Inches(0.7), Inches(0.5),
                    font_name="Garamond", font_size=Pt(20),
                    color=OR_DOUX, align=PP_ALIGN.LEFT)
        add_textbox(sl, name,
                    Inches(7.55), y + Inches(0.05), Inches(4.5), Inches(0.45),
                    font_name="Montserrat", font_size=Pt(12),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        add_rect(sl, Inches(6.8), y + Inches(0.55), Inches(5.5), Inches(0.012),
                 fill_color=RGBColor(0x33, 0x38, 0x4A))

    # Vertical separator
    add_rect(sl, Inches(6.6), Inches(2.15), Inches(0.018), Inches(5.0), fill_color=OR_DOUX)

    add_slide_number_tag(sl, 4)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 5 — BABY-SITTING
# ════════════════════════════════════════════════════════════════════════════
def slide_05():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    # Zone photo droite
    add_rect(sl, Inches(6.8), Inches(0), Inches(6.533), H,
             fill_color=RGBColor(0x2A, 0x30, 0x42))
    add_textbox(sl, "[ Photo baby-sitter & enfant\nchambre d'hôtel luxueuse ]",
                Inches(7.1), Inches(3.0), Inches(5.9), Inches(1.2),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    # Filet vertical doré
    add_rect(sl, Inches(6.78), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)

    # Titre
    add_textbox(sl, "Le baby-sitting,",
                Inches(0.7), Inches(0.95), Inches(5.8), Inches(0.65),
                font_name="Garamond", font_size=Pt(32),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)
    add_textbox(sl, "en toute confiance.",
                Inches(0.7), Inches(1.55), Inches(5.8), Inches(0.55),
                font_name="Garamond", font_size=Pt(26),
                color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(0.7), Inches(2.2), Inches(2.2), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl, "Une présence douce, professionnelle et rassurante\npour veiller sur ce qui compte le plus.",
                Inches(0.7), Inches(2.38), Inches(5.7), Inches(0.8),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    points = [
        "Personnel sélectionné pour son professionnalisme et sa douceur",
        "Garde ponctuelle, régulière ou pendant séjour hôtelier",
        "Encadrement attentif, discret et adapté à l'âge de chaque enfant",
        "Communication fluide avec les parents en temps réel",
        "À domicile, en hôtel ou lors d'événements privés",
    ]

    top = Inches(3.28)
    for pt in points:
        add_textbox(sl, "—  " + pt,
                    Inches(0.7), top, Inches(5.85), Inches(0.42),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.42)

    add_rect(sl, Inches(0.7), Inches(6.32), Inches(5.7), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl,
        '"Parce que la sérénité des parents commence par la confiance\naccordée à la personne qui veille sur leurs enfants."',
        Inches(0.7), Inches(6.42), Inches(5.9), Inches(0.75),
        font_name="Garamond", font_size=Pt(10),
        italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT, line_spacing=Pt(17))

    add_slide_number_tag(sl, 5)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 6 — KIDS CLUB
# ════════════════════════════════════════════════════════════════════════════
def slide_06():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))

    # Zone photo gauche
    add_rect(sl, Inches(0), Inches(0), Inches(6.0), H,
             fill_color=RGBColor(0x22, 0x28, 0x38))
    add_textbox(sl, "[ Photo Kids Club\nenfants & animatrice, riad marocain ]",
                Inches(0.3), Inches(3.2), Inches(5.4), Inches(1.0),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(5.98), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)

    add_logo_mark(sl, x=Inches(6.5), y=Inches(0.22))

    # Badge âge
    add_rect(sl, Inches(6.5), Inches(0.85), Inches(1.35), Inches(0.38),
             fill_color=OR_DOUX)
    add_textbox(sl, "4 — 9 ANS",
                Inches(6.5), Inches(0.88), Inches(1.35), Inches(0.32),
                font_name="Montserrat", font_size=Pt(8),
                bold=True, color=BLEU_NUIT, align=PP_ALIGN.CENTER, char_spacing=300)

    # Titre
    add_textbox(sl, "Un univers de découvertes,\nde créativité et de joie.",
                Inches(6.3), Inches(1.38), Inches(6.55), Inches(1.3),
                font_name="Garamond", font_size=Pt(28),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT, line_spacing=Pt(38))

    add_rect(sl, Inches(6.3), Inches(2.78), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl, "Imaginé pour les plus jeunes, dans un cadre élégant\net parfaitement encadré.",
                Inches(6.3), Inches(2.95), Inches(6.55), Inches(0.75),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    points = [
        "Ateliers créatifs et activités manuelles",
        "Jeux encadrés, lecture et éveil",
        "Animations culturelles et récréatives",
        "Activités en intérieur ou en extérieur",
        "Accompagnement lors de séjours hôteliers et événements privés",
    ]
    top = Inches(3.78)
    for pt in points:
        add_textbox(sl, "—  " + pt,
                    Inches(6.3), top, Inches(6.6), Inches(0.4),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.4)

    add_rect(sl, Inches(6.3), Inches(6.3), Inches(6.6), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl,
        '"Un monde à leur mesure, où l\'imaginaire, le sourire et la douceur prennent toute leur place."',
        Inches(6.3), Inches(6.42), Inches(6.6), Inches(0.75),
        font_name="Garamond", font_size=Pt(10),
        italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT, line_spacing=Pt(17))

    add_slide_number_tag(sl, 6)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 7 — TEEN CLUB
# ════════════════════════════════════════════════════════════════════════════
def slide_07():
    sl = blank_slide(prs)
    set_slide_bg(sl, ANTHRACITE)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    # Zone photo droite
    add_rect(sl, Inches(6.8), Inches(0), Inches(6.533), H,
             fill_color=RGBColor(0x1E, 0x22, 0x2E))
    add_textbox(sl, "[ Photo Teen Club\nadolescents atelier artistique, riad ]",
                Inches(7.2), Inches(3.2), Inches(5.7), Inches(1.0),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(6.78), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)

    # Badge
    add_rect(sl, Inches(0.7), Inches(0.82), Inches(1.55), Inches(0.38),
             fill_color=OR_DOUX)
    add_textbox(sl, "10 — 16 ANS",
                Inches(0.7), Inches(0.85), Inches(1.55), Inches(0.32),
                font_name="Montserrat", font_size=Pt(8),
                bold=True, color=BLEU_NUIT, align=PP_ALIGN.CENTER, char_spacing=300)

    add_textbox(sl, "Un espace dédié aux jeunes,\nentre autonomie, énergie\net expériences encadrées.",
                Inches(0.7), Inches(1.38), Inches(5.8), Inches(1.65),
                font_name="Garamond", font_size=Pt(26),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT, line_spacing=Pt(36))

    add_rect(sl, Inches(0.7), Inches(3.1), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl, "Une approche moderne, dynamique et pleinement adaptée\nà leur univers.",
                Inches(0.7), Inches(3.28), Inches(5.8), Inches(0.7),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    points = [
        "Activités de groupe et défis ludiques",
        "Ateliers créatifs modernes et jeux interactifs",
        "Animations adaptées aux hôtels, resorts et événements",
        "Encadrement professionnel discret et bienveillant",
        "Expression de soi, convivialité et sens du dépassement",
        "Équilibre subtil entre liberté et cadre structurant",
    ]
    top = Inches(4.05)
    for pt in points:
        add_textbox(sl, "—  " + pt,
                    Inches(0.7), top, Inches(5.85), Inches(0.38),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.36)

    add_rect(sl, Inches(0.7), Inches(6.3), Inches(5.8), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl,
        '"Un espace pensé pour les jeunes d\'aujourd\'hui, avec l\'élégance d\'un service qui comprend vraiment leurs besoins."',
        Inches(0.7), Inches(6.42), Inches(5.9), Inches(0.75),
        font_name="Garamond", font_size=Pt(10),
        italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT, line_spacing=Pt(17))

    add_slide_number_tag(sl, 7)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 8 — EXPÉRIENCES OUTDOOR (slide visuelle)
# ════════════════════════════════════════════════════════════════════════════
def slide_08():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    # Fond photo simulé
    add_rect(sl, Inches(0), Inches(0), W, H,
             fill_color=RGBColor(0x1A, 0x22, 0x30))

    add_textbox(sl,
        "[ Photo pleine page — Enfants jouant sur pelouse de palace marocain\n"
        "Coucher de soleil, palmiers, animatrice, atmosphère magique ]",
        Inches(3.0), Inches(3.0), Inches(7.3), Inches(1.2),
        font_name="Montserrat", font_size=Pt(9),
        italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    # Overlay gradient bas (simulé)
    add_rect(sl, Inches(0), Inches(4.5), W, Inches(3.0),
             fill_color=RGBColor(0x0A, 0x0E, 0x18))

    add_gold_bar(sl, H - Inches(0.028))

    add_textbox(sl, "Des moments vivants, encadrés et mémorables.",
                Inches(0.8), Inches(4.8), Inches(9.0), Inches(0.85),
                font_name="Garamond", font_size=Pt(36),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)

    add_textbox(sl, "Dans les jardins, les espaces extérieurs et les terrasses des plus beaux établissements.",
                Inches(0.8), Inches(5.65), Inches(9.0), Inches(0.45),
                font_name="Garamond", font_size=Pt(14),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    msgs = [
        "Un encadrement pensé pour chaque instant",
        "Activités alliant plaisir, sécurité et découverte",
        "Une présence humaine discrète mais toujours attentive",
    ]
    top = Inches(6.25)
    for m in msgs:
        add_textbox(sl, "◆  " + m,
                    Inches(0.8), top, Inches(9.0), Inches(0.35),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.32)

    add_slide_number_tag(sl, 8)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 9 — APPLICATION FAIZA MULTISERVICE
# ════════════════════════════════════════════════════════════════════════════
def slide_09():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))

    # Zone photo droite
    add_rect(sl, Inches(6.8), Inches(0), Inches(6.533), H,
             fill_color=RGBColor(0x22, 0x28, 0x38))
    add_textbox(sl, "[ Photo mère avec tablette\nchambre riad premium, baby-sitter & enfant ]",
                Inches(7.2), Inches(3.2), Inches(5.7), Inches(1.0),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(6.78), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)
    add_logo_mark(sl)

    add_textbox(sl, "Une application pensée pour",
                Inches(0.7), Inches(0.95), Inches(5.8), Inches(0.55),
                font_name="Garamond", font_size=Pt(28),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)
    add_textbox(sl, "simplifier chaque réservation.",
                Inches(0.7), Inches(1.48), Inches(5.8), Inches(0.55),
                font_name="Garamond", font_size=Pt(22),
                color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(0.7), Inches(2.12), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl, "La technologie au service d'une expérience plus fluide,\nplus simple et plus rassurante.",
                Inches(0.7), Inches(2.3), Inches(5.8), Inches(0.75),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    fonctionnalites = [
        "Réservation rapide via QR Code ou NFC",
        "Choix de la date, l'heure et du nombre d'enfants",
        "Formulaire simple et structuré",
        "Suivi en temps réel de la demande",
        "Confirmation et échanges via WhatsApp",
        "Gestion centralisée des réservations",
        "Interface intuitive pour familles et hôtels",
    ]
    top = Inches(3.15)
    for f in fonctionnalites:
        add_textbox(sl, "◆  " + f,
                    Inches(0.7), top, Inches(5.85), Inches(0.38),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.38)

    add_rect(sl, Inches(0.7), Inches(6.32), Inches(5.8), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl,
        '"Une solution digitale élégante, conçue pour rendre le service encore plus fluide\nsans jamais perdre sa dimension humaine."',
        Inches(0.7), Inches(6.42), Inches(5.9), Inches(0.75),
        font_name="Garamond", font_size=Pt(10),
        italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT, line_spacing=Pt(17))

    add_slide_number_tag(sl, 9)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 10 — SERVICE HUMAIN & ASSISTANCE
# ════════════════════════════════════════════════════════════════════════════
def slide_10():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))

    # Zone photo gauche
    add_rect(sl, Inches(0), Inches(0), Inches(6.0), H,
             fill_color=RGBColor(0x22, 0x28, 0x38))
    add_textbox(sl, "[ Photo collaboratrice & dame âgée élégante\nriad, lumière dorée chaude ]",
                Inches(0.3), Inches(3.2), Inches(5.4), Inches(1.0),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(5.98), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)
    add_logo_mark(sl, x=Inches(6.5), y=Inches(0.22))

    add_textbox(sl, "Le service humain,",
                Inches(6.3), Inches(0.95), Inches(6.55), Inches(0.6),
                font_name="Garamond", font_size=Pt(30),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)
    add_textbox(sl, "l'élégance du quotidien.",
                Inches(6.3), Inches(1.52), Inches(6.55), Inches(0.55),
                font_name="Garamond", font_size=Pt(24),
                color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(6.3), Inches(2.15), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl,
        "Parce qu'un service d'exception ne se mesure pas à la richesse\nde ses moyens, mais à la qualité de sa présence.",
        Inches(6.3), Inches(2.32), Inches(6.55), Inches(0.85),
        font_name="Garamond", font_size=Pt(12),
        italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(20))

    points = [
        "Aide ponctuelle ou régulière au quotidien",
        "Accompagnement : courses, repas, promenades",
        "Soins de confort : coiffure, aide à l'habillement",
        "Présence bienveillante et compagnie rassurante",
        "Interface discrète entre famille et intervenants médicaux",
        "Service digne & raffiné — le soin du lien professionnel",
    ]
    top = Inches(3.28)
    for pt in points:
        add_textbox(sl, "—  " + pt,
                    Inches(6.3), top, Inches(6.6), Inches(0.38),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.38)

    add_rect(sl, Inches(6.3), Inches(6.32), Inches(6.6), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl, '"Servir avec cœur, accompagner avec élégance."',
                Inches(6.3), Inches(6.42), Inches(6.6), Inches(0.6),
                font_name="Garamond", font_size=Pt(13),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_slide_number_tag(sl, 10)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 11 — ÉVÉNEMENTIEL PRIVÉ
# ════════════════════════════════════════════════════════════════════════════
def slide_11():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    # Fond photo pleine page
    add_rect(sl, Inches(0), Inches(0), W, H,
             fill_color=RGBColor(0x18, 0x1C, 0x26))

    add_textbox(sl,
        "[ Photo décoration florale somptueuse, table dressée bord de piscine,\nriad fleuri, chandelles, lumière dorée magique ]",
        Inches(4.0), Inches(2.0), Inches(5.3), Inches(1.2),
        font_name="Montserrat", font_size=Pt(8),
        italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    # Overlay bas
    add_rect(sl, Inches(0), Inches(3.8), W, Inches(3.7),
             fill_color=RGBColor(0x0A, 0x0E, 0x18))

    add_gold_bar(sl, H - Inches(0.028))
    add_gold_bar(sl, Inches(0))

    add_textbox(sl, "L'art de célébrer avec raffinement.",
                Inches(0.8), Inches(4.05), Inches(12.7), Inches(0.85),
                font_name="Garamond", font_size=Pt(38),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER)

    add_textbox(sl,
        "Mariages · Anniversaires · Demandes en mariage · Voyages de noces · Dîners étoilés",
        Inches(1.0), Inches(4.9), Inches(11.3), Inches(0.45),
        font_name="Garamond", font_size=Pt(14),
        italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(4.0), Inches(5.48), Inches(5.3), Inches(0.018), fill_color=OR_DOUX)

    domaines = [
        "Mariages & réceptions privées",
        "Dîners d'entreprise et soirées exclusives",
        "Anniversaires et demandes en mariage sur mesure",
        "Décoration, mise en scène & coordination complète",
    ]
    top = Inches(5.65)
    for d in domaines:
        add_textbox(sl, "◆  " + d,
                    Inches(1.5), top, Inches(10.3), Inches(0.32),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.CENTER)
        top += Inches(0.32)

    add_rect(sl, Inches(4.0), Inches(6.95), Inches(5.3), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl, '"L\'élégance d\'un instant, la perfection d\'un souvenir."',
                Inches(1.5), Inches(7.05), Inches(10.3), Inches(0.35),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    add_slide_number_tag(sl, 11)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 12 — CONCIERGERIE PRIVÉE
# ════════════════════════════════════════════════════════════════════════════
def slide_12():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    # Zone photo droite
    add_rect(sl, Inches(6.8), Inches(0), Inches(6.533), H,
             fill_color=RGBColor(0x22, 0x28, 0x38))
    add_textbox(sl, "[ Photo concierge palace\nhôtel marocain luxueux ]",
                Inches(7.2), Inches(3.2), Inches(5.7), Inches(1.0),
                font_name="Montserrat", font_size=Pt(8),
                italic=True, color=RGBColor(0x44, 0x48, 0x55), align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(6.78), Inches(0), Inches(0.022), H, fill_color=OR_DOUX)

    add_textbox(sl, "Conciergerie privée",
                Inches(0.7), Inches(0.92), Inches(5.8), Inches(0.6),
                font_name="Garamond", font_size=Pt(30),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)
    add_textbox(sl, "& assistance exclusive.",
                Inches(0.7), Inches(1.5), Inches(5.8), Inches(0.55),
                font_name="Garamond", font_size=Pt(24),
                color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(0.7), Inches(2.12), Inches(2.5), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl, "Le luxe ultime : ne plus avoir à s'en soucier.",
                Inches(0.7), Inches(2.32), Inches(5.8), Inches(0.5),
                font_name="Garamond", font_size=Pt(14),
                italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT)

    add_textbox(sl,
        "Notre service combine discrétion, réactivité et excellence du détail\npour un accompagnement intégral et parfaitement maîtrisé.",
        Inches(0.7), Inches(2.9), Inches(5.8), Inches(0.8),
        font_name="Montserrat", font_size=Pt(10.5),
        color=BLANC_CASSE, align=PP_ALIGN.LEFT, line_spacing=Pt(18))

    services_conc = [
        "Réservations : restaurants, hôtels, événements privés",
        "Transferts & accueil aéroport / gare",
        "Livraison de cadeaux & gestion d'imprévus",
        "Organisation de séjours personnalisés",
        "Courses, intendance et services urgents",
        "Coordination multilingue (français, anglais, arabe)",
    ]
    top = Inches(3.82)
    for s in services_conc:
        add_textbox(sl, "—  " + s,
                    Inches(0.7), top, Inches(5.85), Inches(0.38),
                    font_name="Montserrat", font_size=Pt(10),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top += Inches(0.38)

    add_rect(sl, Inches(0.7), Inches(6.32), Inches(5.8), Inches(0.015), fill_color=OR_DOUX)
    add_textbox(sl, '"Votre confort, notre mission. Votre confiance, notre signature."',
                Inches(0.7), Inches(6.42), Inches(5.8), Inches(0.6),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_slide_number_tag(sl, 12)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 13 — TÉMOIGNAGES CLIENTS
# ════════════════════════════════════════════════════════════════════════════
def slide_13():
    sl = blank_slide(prs)
    set_slide_bg(sl, ANTHRACITE)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    add_textbox(sl, "Ils nous font confiance.",
                Inches(1.0), Inches(0.75), Inches(11.3), Inches(0.65),
                font_name="Garamond", font_size=Pt(34),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER)

    add_textbox(sl, "La satisfaction de nos clients est notre plus belle reconnaissance.",
                Inches(2.0), Inches(1.42), Inches(9.3), Inches(0.38),
                font_name="Garamond", font_size=Pt(12),
                italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    add_rect(sl, Inches(5.4), Inches(1.9), Inches(2.5), Inches(0.018), fill_color=OR_DOUX)

    temoignages = [
        ('"Faiza Multiservice fait preuve d\'un professionnalisme remarquable.\nLeur équipe comprend parfaitement les exigences du service hôtelier."',
         "Sofitel — Partenaire hôtelier"),
        ('"Collaborer avec Faiza Multiservice, c\'est choisir la confiance.\nLeur sens du détail fait toute la différence."',
         "W Hotels — Partenaire"),
        ('"Un service fluide, professionnel et humain.\nNous ne nous en passons plus."',
         "Famille Benhima — Casablanca"),
        ('"Pendant nos vacances à Agadir, ils ont pris soin de nos enfants\navec une douceur et une attention incroyables."',
         "Dan Cohen — Tel Aviv"),
        ('"La bienveillance et le professionnalisme m\'ont profondément touchée.\nUn service d\'une qualité rare."',
         "Mme Zahra El Amrani — Agadir"),
    ]

    card_w = Inches(3.85)
    card_h = Inches(1.85)
    positions = [
        (Inches(0.5),  Inches(2.15)),
        (Inches(4.72), Inches(2.15)),
        (Inches(8.95), Inches(2.15)),
        (Inches(2.6),  Inches(4.2)),
        (Inches(6.85), Inches(4.2)),
    ]

    for i, ((cx, cy), (quote, author)) in enumerate(zip(positions, temoignages)):
        # Fond carte
        add_rect(sl, cx, cy, card_w, card_h, fill_color=BLEU_NUIT)

        # Guillemet décoratif
        add_textbox(sl, "“",
                    cx + Inches(0.15), cy + Inches(0.05),
                    Inches(0.5), Inches(0.6),
                    font_name="Garamond", font_size=Pt(36),
                    color=OR_DOUX, align=PP_ALIGN.LEFT)

        # Citation
        add_textbox(sl, quote,
                    cx + Inches(0.18), cy + Inches(0.52),
                    card_w - Inches(0.3), Inches(0.9),
                    font_name="Garamond", font_size=Pt(9),
                    italic=True, color=BLANC_CASSE, align=PP_ALIGN.LEFT,
                    line_spacing=Pt(14))

        # Filet
        add_rect(sl, cx + Inches(0.18), cy + card_h - Inches(0.5),
                 card_w - Inches(0.35), Inches(0.012), fill_color=OR_DOUX)

        # Auteur
        add_textbox(sl, author,
                    cx + Inches(0.18), cy + card_h - Inches(0.42),
                    card_w - Inches(0.3), Inches(0.35),
                    font_name="Montserrat", font_size=Pt(7.5),
                    bold=True, color=OR_DOUX, align=PP_ALIGN.LEFT, char_spacing=200)

    add_slide_number_tag(sl, 13)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 14 — ENGAGEMENT FAIZA MULTISERVICE
# ════════════════════════════════════════════════════════════════════════════
def slide_14():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    add_textbox(sl, "Un engagement fondé sur la confiance,\nla qualité et la discrétion.",
                Inches(0.8), Inches(0.75), Inches(11.7), Inches(1.25),
                font_name="Garamond", font_size=Pt(32),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER, line_spacing=Pt(44))

    add_rect(sl, Inches(4.5), Inches(2.1), Inches(4.3), Inches(0.02), fill_color=OR_DOUX)

    add_textbox(sl,
        "Chaque collaborateur est formé et sélectionné pour garantir\nun service humain, éthique et d'une exigence absolue.",
        Inches(1.5), Inches(2.3), Inches(10.3), Inches(0.65),
        font_name="Garamond", font_size=Pt(13),
        italic=True, color=GRIS_PIERRE, align=PP_ALIGN.CENTER, line_spacing=Pt(22))

    piliers_eng = [
        ("SÉCURITÉ &\nBIENVEILLANCE",
         "Chaque membre est sélectionné, formé et évalué selon les standards les plus rigoureux. Votre sécurité est notre priorité absolue."),
        ("CONFIANCE &\nTRANSPARENCE",
         "Nous privilégions la clarté avec nos clients à chaque étape de la mission. Chaque demande est traitée avec soin et confidentialité."),
        ("RESPONSABILITÉ &\nAUTHENTICITÉ",
         "Nous valorisons le travail local, l'éthique et un service inspiré par les valeurs marocaines de respect et d'hospitalité."),
    ]

    col_w = Inches(3.7)
    start_x = Inches(0.8)
    top_y = Inches(3.15)

    for i, (titre, desc) in enumerate(piliers_eng):
        cx = start_x + i * col_w

        # Cercle doré simulé (carré avec fond doré léger)
        add_rect(sl, cx + Inches(1.25), top_y, Inches(1.2), Inches(0.06),
                 fill_color=OR_DOUX)

        add_textbox(sl, titre,
                    cx, top_y + Inches(0.2), col_w - Inches(0.2), Inches(0.9),
                    font_name="Montserrat", font_size=Pt(11),
                    bold=True, color=BLANC_CASSE, align=PP_ALIGN.CENTER,
                    char_spacing=200, line_spacing=Pt(18))

        add_rect(sl, cx + Inches(0.8), top_y + Inches(1.18),
                 Inches(2.1), Inches(0.015), fill_color=OR_DOUX)

        add_textbox(sl, desc,
                    cx + Inches(0.1), top_y + Inches(1.35),
                    col_w - Inches(0.2), Inches(2.5),
                    font_name="Garamond", font_size=Pt(11.5),
                    italic=True, color=GRIS_PIERRE, align=PP_ALIGN.CENTER,
                    line_spacing=Pt(20))

    # Séparateurs verticaux
    add_rect(sl, Inches(4.5), top_y, Inches(0.018), Inches(4.0), fill_color=RGBColor(0x33, 0x38, 0x4A))
    add_rect(sl, Inches(8.2), top_y, Inches(0.018), Inches(4.0), fill_color=RGBColor(0x33, 0x38, 0x4A))

    add_slide_number_tag(sl, 14)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 15 — POURQUOI CHOISIR FAIZA MULTISERVICE
# ════════════════════════════════════════════════════════════════════════════
def slide_15():
    sl = blank_slide(prs)
    set_slide_bg(sl, ANTHRACITE)

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))
    add_logo_mark(sl)

    add_textbox(sl, "Une réponse haut de gamme aux besoins du quotidien.",
                Inches(0.7), Inches(0.75), Inches(11.9), Inches(0.65),
                font_name="Garamond", font_size=Pt(30),
                color=BLANC_CASSE, align=PP_ALIGN.LEFT)

    add_textbox(sl, "Ce qui nous distingue, c'est la qualité de notre présence — humaine, professionnelle et toujours personnalisée.",
                Inches(0.7), Inches(1.48), Inches(10.0), Inches(0.4),
                font_name="Garamond", font_size=Pt(13),
                italic=True, color=OR_DOUX, align=PP_ALIGN.LEFT)

    add_rect(sl, Inches(0.7), Inches(2.0), Inches(12.0), Inches(0.018), fill_color=RGBColor(0x33, 0x38, 0x4A))

    arguments = [
        ("01", "SERVICE HUMAIN",
         "Des intervenants sélectionnés pour leur savoir-faire,\nleur élégance et leur sens profond du service."),
        ("02", "EXPÉRIENCE PREMIUM",
         "Un niveau d'excellence inspiré des standards\ndes grands hôtels et palaces internationaux."),
        ("03", "FLEXIBILITÉ TOTALE",
         "Ponctuel, régulier ou sur mesure — nous nous adaptons\nà chaque besoin, chaque rythme de vie."),
        ("04", "ENCADREMENT FIABLE",
         "Disponibles 7j/7, réactifs, coordonnés via\nune gestion professionnelle centralisée."),
        ("05", "PARTENAIRE HÔTELS & PRIVÉS",
         "Une solution qui comprend les exigences des hôtels\ncomme des familles et clients privés les plus exigeants."),
    ]

    top = Inches(2.2)
    row_h = Inches(0.98)

    for num, titre, desc in arguments:
        # Numéro
        add_textbox(sl, num,
                    Inches(0.7), top, Inches(0.9), Inches(0.75),
                    font_name="Garamond", font_size=Pt(28),
                    color=OR_DOUX, align=PP_ALIGN.LEFT)

        # Titre argument
        add_textbox(sl, titre,
                    Inches(1.7), top + Inches(0.08), Inches(3.2), Inches(0.4),
                    font_name="Montserrat", font_size=Pt(10),
                    bold=True, color=BLANC_CASSE, align=PP_ALIGN.LEFT, char_spacing=250)

        # Description
        add_textbox(sl, desc,
                    Inches(1.7), top + Inches(0.45), Inches(10.7), Inches(0.55),
                    font_name="Garamond", font_size=Pt(11.5),
                    italic=True, color=GRIS_PIERRE, align=PP_ALIGN.LEFT, line_spacing=Pt(18))

        # Séparateur
        add_rect(sl, Inches(0.7), top + Inches(0.92), Inches(12.0), Inches(0.012),
                 fill_color=RGBColor(0x33, 0x38, 0x4A))

        top += row_h

    add_slide_number_tag(sl, 15)
    return sl

# ════════════════════════════════════════════════════════════════════════════
# SLIDE 16 — CONCLUSION & CONTACT
# ════════════════════════════════════════════════════════════════════════════
def slide_16():
    sl = blank_slide(prs)
    set_slide_bg(sl, BLEU_NUIT)

    # Motif fond subtil
    for i in range(0, 28):
        xi = Inches(i * 0.5 - 1)
        add_rect(sl, xi, Inches(0), Inches(0.03), H,
                 fill_color=RGBColor(0x2A, 0x30, 0x42))

    add_gold_bar(sl, Inches(0.0))
    add_gold_bar(sl, H - Inches(0.028))

    # Monogramme grand
    add_textbox(sl, "KF",
                Inches(5.7), Inches(0.4), Inches(2.0), Inches(0.9),
                font_name="Garamond", font_size=Pt(42),
                bold=False, color=OR_DOUX, align=PP_ALIGN.CENTER, char_spacing=300)

    add_rect(sl, Inches(5.0), Inches(1.32), Inches(3.3), Inches(0.018), fill_color=ARGENT)

    add_textbox(sl, "FAIZA MULTISERVICE",
                Inches(3.0), Inches(1.45), Inches(7.3), Inches(0.45),
                font_name="Montserrat", font_size=Pt(10),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER, char_spacing=500)

    add_textbox(sl, "SERVICE CLIENT DE QUALITÉ",
                Inches(3.5), Inches(1.88), Inches(6.3), Inches(0.32),
                font_name="Montserrat", font_size=Pt(7),
                color=ARGENT, align=PP_ALIGN.CENTER, char_spacing=400)

    add_rect(sl, Inches(4.5), Inches(2.28), Inches(4.3), Inches(0.022), fill_color=OR_DOUX)

    add_textbox(sl, "Votre tranquillité d'esprit commence ici.",
                Inches(1.0), Inches(2.55), Inches(11.3), Inches(0.75),
                font_name="Garamond", font_size=Pt(32),
                color=BLANC_CASSE, align=PP_ALIGN.CENTER)

    add_textbox(sl,
        "Réserver un service Faiza Multiservice, c'est choisir la sérénité.\n"
        "En quelques échanges, notre équipe définit vos besoins, sélectionne les meilleurs profils\n"
        "et assure la coordination complète de votre mission.",
        Inches(1.5), Inches(3.42), Inches(10.3), Inches(1.0),
        font_name="Garamond", font_size=Pt(13),
        italic=True, color=GRIS_PIERRE, align=PP_ALIGN.CENTER, line_spacing=Pt(22))

    add_rect(sl, Inches(4.5), Inches(4.55), Inches(4.3), Inches(0.018), fill_color=OR_DOUX)

    # Coordonnées — 2 colonnes
    contacts_g = [
        ("WhatsApp / Téléphone", "+212 639 421 639"),
        ("Email",                "faizamultiservice@gmail.com"),
    ]
    contacts_d = [
        ("Adresse",  "Agadir, Maroc"),
        ("Site web", "www.faizamultiservice.com"),
    ]

    top_c = Inches(4.78)
    row_c = Inches(0.68)

    for label, val in contacts_g:
        add_textbox(sl, label.upper(),
                    Inches(1.5), top_c, Inches(5.3), Inches(0.28),
                    font_name="Montserrat", font_size=Pt(7.5),
                    color=ARGENT, align=PP_ALIGN.RIGHT, char_spacing=300)
        add_textbox(sl, val,
                    Inches(1.5), top_c + Inches(0.27), Inches(5.3), Inches(0.35),
                    font_name="Garamond", font_size=Pt(15),
                    color=BLANC_CASSE, align=PP_ALIGN.RIGHT)
        top_c += row_c

    top_c = Inches(4.78)
    for label, val in contacts_d:
        add_textbox(sl, label.upper(),
                    Inches(6.5), top_c, Inches(5.3), Inches(0.28),
                    font_name="Montserrat", font_size=Pt(7.5),
                    color=ARGENT, align=PP_ALIGN.LEFT, char_spacing=300)
        add_textbox(sl, val,
                    Inches(6.5), top_c + Inches(0.27), Inches(5.3), Inches(0.35),
                    font_name="Garamond", font_size=Pt(15),
                    color=BLANC_CASSE, align=PP_ALIGN.LEFT)
        top_c += row_c

    # Séparateur vertical contact
    add_rect(sl, Inches(6.63), Inches(4.72), Inches(0.018), Inches(1.55),
             fill_color=OR_DOUX)

    add_rect(sl, Inches(4.5), Inches(6.52), Inches(4.3), Inches(0.018), fill_color=OR_DOUX)

    add_textbox(sl, '"Relax, We handle the rest."',
                Inches(2.0), Inches(6.65), Inches(9.3), Inches(0.5),
                font_name="Garamond", font_size=Pt(18),
                italic=True, color=OR_DOUX, align=PP_ALIGN.CENTER)

    add_slide_number_tag(sl, 16)
    return sl


# ─── GÉNÉRATION DES 16 SLIDES ────────────────────────────────────────────────
print("Génération des slides...")
slide_01()
print("  ✓ Slide 1  — Couverture")
slide_02()
print("  ✓ Slide 2  — Introduction")
slide_03()
print("  ✓ Slide 3  — Vision & Promesse")
slide_04()
print("  ✓ Slide 4  — Univers de service")
slide_05()
print("  ✓ Slide 5  — Baby-sitting")
slide_06()
print("  ✓ Slide 6  — Kids Club")
slide_07()
print("  ✓ Slide 7  — Teen Club")
slide_08()
print("  ✓ Slide 8  — Expériences Outdoor")
slide_09()
print("  ✓ Slide 9  — Application")
slide_10()
print("  ✓ Slide 10 — Service humain")
slide_11()
print("  ✓ Slide 11 — Événementiel privé")
slide_12()
print("  ✓ Slide 12 — Conciergerie privée")
slide_13()
print("  ✓ Slide 13 — Témoignages")
slide_14()
print("  ✓ Slide 14 — Engagement")
slide_15()
print("  ✓ Slide 15 — Pourquoi nous choisir")
slide_16()
print("  ✓ Slide 16 — Conclusion & Contact")

output_path = "/home/user/OptrixisAITrading/FAIZA_MULTISERVICE_Presentation_Premium.pptx"
prs.save(output_path)
print(f"\n✅ Fichier généré : {output_path}")
print(f"   Slides : 16 | Format : 16:9 widescreen | Taille : ~", end="")
import os
print(f"{os.path.getsize(output_path) // 1024} Ko")
