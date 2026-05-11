# Article Post Structure Analysis

**Date:** May 11, 2026  
**Template:** `single-post.twig`  
**Status:** ⚠️ Good but Missing Some Best Practices

---

## Current Structure

```
1. Breadcrumb
2. Header Section
   - Category badge + Date + Author (inline)
   - Title (H1)
   - Preview/Excerpt
3. Featured Image
4. Meta Info Bar (duplicate: Date + Category + Author)
5. Content
6. Share Actions
7. Sidebar (sticky)
   - Info artikel (Date, Author, Category)
   - Popular articles
8. Related Articles Section
```

---

## ✅ What's Good (Already Implemented)

### 1. **Basic Structure** ✅
- Clear hierarchy
- Breadcrumb navigation
- Proper H1 title
- Featured image
- Content area
- Sidebar with related content

### 2. **Metadata Display** ✅
- Publication date
- Author name
- Category
- Preview/excerpt

### 3. **Social Sharing** ✅
- Share buttons (WhatsApp, Facebook, X)
- Positioned after content

### 4. **Related Content** ✅
- Related articles section
- Popular articles in sidebar

### 5. **Responsive Layout** ✅
- Grid layout with sidebar
- Sticky sidebar
- Mobile-friendly

---

## ❌ What's Missing (Best Practices)

### 1. **Reading Time Indicator** ❌
**Standard:** Most modern blogs show estimated reading time

**Example:**
```
📖 5 min read
```

**Why Important:**
- Helps users decide if they have time to read
- Improves user experience
- Standard in Medium, Dev.to, etc.

**Where to Add:** Next to date/author in header

---

### 2. **Author Bio/Card** ❌
**Standard:** Author information with photo and bio

**Example:**
```
┌─────────────────────────────────────┐
│ [Photo] Dr. John Doe                │
│         Dokter Spesialis Jantung    │
│         Brief bio about the author  │
│         [Social Links]              │
└─────────────────────────────────────┘
```

**Why Important:**
- Builds trust and credibility
- Humanizes content
- Encourages author following

**Where to Add:** After content, before related articles

---

### 3. **Table of Contents (TOC)** ❌
**Standard:** For long articles (>1000 words)

**Example:**
```
📑 Daftar Isi
1. Introduction
2. Main Topic
3. Conclusion
```

**Why Important:**
- Improves navigation for long articles
- Better UX for scanners
- SEO benefits

**Where to Add:** Sidebar (sticky) or after header

---

### 4. **Last Updated Date** ❌
**Standard:** Show when article was last updated

**Example:**
```
Diterbitkan: 10 Mei 2026
Diperbarui: 11 Mei 2026
```

**Why Important:**
- Shows content freshness
- Important for medical/health content
- Builds trust

**Where to Add:** In header metadata

---

### 5. **Tags/Keywords** ❌
**Standard:** Article tags for better categorization

**Example:**
```
🏷️ Tags: Kesehatan Jantung, Pencegahan, Tips Sehat
```

**Why Important:**
- Better content discovery
- SEO benefits
- Related content linking

**Where to Add:** After content or in sidebar

---

### 6. **Print/Save/Bookmark Actions** ❌
**Standard:** Additional actions beyond sharing

**Example:**
```
[Print] [Save] [Bookmark]
```

**Why Important:**
- User convenience
- Offline reading
- Content preservation

**Where to Add:** Near share buttons

---

### 7. **Article Schema/Structured Data** ❓
**Standard:** JSON-LD for SEO

**Why Important:**
- Rich snippets in search results
- Better SEO
- Google News eligibility

**Where to Add:** In `<head>` or footer

---

### 8. **Comments Section** ❌
**Standard:** User engagement through comments

**Why Important:**
- Community engagement
- User feedback
- Content discussion

**Where to Add:** After content, before related articles

---

### 9. **Progress Bar** ❌
**Standard:** Reading progress indicator

**Example:**
```
[████████░░░░░░░░] 60% read
```

**Why Important:**
- Visual feedback
- Encourages completion
- Modern UX pattern

**Where to Add:** Fixed at top of page

---

### 10. **Duplicate Metadata** ⚠️
**Issue:** Date, Category, Author shown 3 times:
1. In header (inline with category badge)
2. In meta bar (border-y section)
3. In sidebar (Info artikel)

**Recommendation:** Remove duplicate meta bar, keep only:
- Header: Category badge + Date + Author
- Sidebar: Extended info if needed

---

## 📊 Comparison with Industry Standards

### Medium.com Structure:
```
1. Title
2. Author card (photo + name + date + reading time)
3. Featured image
4. Content
5. Tags
6. Claps/Reactions
7. Author bio card
8. Related articles
```

### Dev.to Structure:
```
1. Title
2. Author + Date + Reading time + Tags
3. Featured image
4. Table of contents (sidebar)
5. Content
6. Reactions + Comments
7. Author card
8. Related articles
```

### Healthcare Blog Standards (Healthline, WebMD):
```
1. Title
2. Medically reviewed by [Expert]
3. Author + Date + Last updated
4. Table of contents
5. Featured image
6. Content with citations
7. Key takeaways box
8. References/Sources
9. Author bio
10. Related articles
```

---

## 🎯 Recommendations (Priority Order)

### High Priority (Should Have):

#### 1. **Remove Duplicate Metadata** 🔴
**Current:** 3x duplicate (header, meta bar, sidebar)  
**Fix:** Remove meta bar (border-y section)  
**Impact:** Cleaner UI, less redundancy

#### 2. **Add Reading Time** 🔴
**Location:** Header, next to date  
**Format:** "📖 5 min read"  
**Impact:** Better UX, industry standard

#### 3. **Add Last Updated Date** 🔴
**Location:** Header metadata  
**Format:** "Diperbarui: 11 Mei 2026"  
**Impact:** Content freshness, trust (important for health content)

#### 4. **Add Author Bio Card** 🔴
**Location:** After content, before related articles  
**Content:** Photo, name, title, bio, social links  
**Impact:** Credibility, trust, engagement

---

### Medium Priority (Nice to Have):

#### 5. **Add Tags** 🟡
**Location:** After content or sidebar  
**Impact:** Better categorization, SEO

#### 6. **Add Table of Contents** 🟡
**Location:** Sidebar (for long articles)  
**Condition:** Only for articles >1000 words  
**Impact:** Better navigation

#### 7. **Add Print/Save Actions** 🟡
**Location:** Near share buttons  
**Impact:** User convenience

---

### Low Priority (Optional):

#### 8. **Add Comments Section** 🟢
**Location:** After content  
**Impact:** Engagement (but requires moderation)

#### 9. **Add Progress Bar** 🟢
**Location:** Fixed at top  
**Impact:** Modern UX

#### 10. **Add Structured Data** 🟢
**Location:** JSON-LD in head  
**Impact:** SEO, rich snippets

---

## 🔧 Proposed Improved Structure

```
1. Breadcrumb ✅
2. Header Section (IMPROVED)
   - Category badge
   - Title (H1)
   - Author photo + name + Date + Last updated + Reading time ← NEW
   - Preview/Excerpt
3. Featured Image ✅
4. [REMOVED: Duplicate meta bar] ← REMOVE
5. Content ✅
6. Tags ← NEW
7. Share Actions ✅
8. Author Bio Card ← NEW
9. Sidebar (sticky) ✅
   - Table of Contents ← NEW (for long articles)
   - Info artikel (simplified)
   - Popular articles
10. Related Articles Section ✅
```

---

## 📝 Implementation Checklist

### Phase 1: Critical Fixes (Do Now)
- [ ] Remove duplicate meta bar (border-y section)
- [ ] Add reading time calculation
- [ ] Add last updated date field
- [ ] Create author bio component
- [ ] Add author bio after content

### Phase 2: Enhancements (Do Soon)
- [ ] Add tags support
- [ ] Add table of contents (conditional)
- [ ] Add print/save actions
- [ ] Improve header metadata layout

### Phase 3: Advanced (Do Later)
- [ ] Add comments section
- [ ] Add progress bar
- [ ] Add structured data (JSON-LD)
- [ ] Add reactions/likes

---

## 🎨 Visual Mockup (Proposed)

```
┌─────────────────────────────────────────────────────────┐
│ Home > Berita & Artikel > Kesehatan                     │ Breadcrumb
├─────────────────────────────────────────────────────────┤
│                                                          │
│ [Kesehatan] ← Category badge                           │
│                                                          │
│ Tips Menjaga Kesehatan Jantung ← H1 Title              │
│                                                          │
│ [👤 Photo] Dr. John Doe • 10 Mei 2026 •                │ ← NEW
│           Diperbarui: 11 Mei 2026 • 📖 5 min read      │
│                                                          │
│ Brief excerpt about the article content...              │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ [Featured Image]                                        │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Article content here...                                 │
│                                                          │
│ More content...                                         │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ 🏷️ Tags: Kesehatan Jantung, Pencegahan, Tips          │ ← NEW
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ 🔗 Bagikan artikel                                      │
│ [WhatsApp] [Facebook] [X] [Print] [Save]               │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Tentang Penulis                                         │ ← NEW
│ ┌───────────────────────────────────────────────────┐  │
│ │ [Photo] Dr. John Doe                              │  │
│ │         Dokter Spesialis Jantung                  │  │
│ │         RS PKU Muhammadiyah Yogyakarta            │  │
│ │                                                    │  │
│ │         Brief bio about the author and their      │  │
│ │         expertise in the field...                 │  │
│ │                                                    │  │
│ │         [LinkedIn] [Email]                        │  │
│ └───────────────────────────────────────────────────┘  │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Artikel Terkait                                         │
│ [Card 1] [Card 2] [Card 3]                             │
│                                                          │
└─────────────────────────────────────────────────────────┘

SIDEBAR (Sticky):
┌─────────────────────┐
│ 📑 Daftar Isi       │ ← NEW (for long articles)
│ 1. Introduction     │
│ 2. Main Topic       │
│ 3. Conclusion       │
├─────────────────────┤
│ Info Artikel        │
│ • Tanggal terbit    │
│ • Penulis           │
│ • Kategori          │
├─────────────────────┤
│ Populer Dibaca      │
│ [Article 1]         │
│ [Article 2]         │
│ [Article 3]         │
└─────────────────────┘
```

---

## 📚 References

### Industry Standards:
- Medium.com article structure
- Dev.to blog post layout
- Healthline medical article format
- WebMD health content structure
- Google's Article structured data guidelines

### Best Practices:
- Nielsen Norman Group - Article Page Usability
- Smashing Magazine - Blog Post Design Patterns
- Content Marketing Institute - Blog Post Structure

---

## Conclusion

**Current Status:** ⭐⭐⭐⭐ (4/5)

**Strengths:**
- ✅ Clean, readable layout
- ✅ Good basic structure
- ✅ Responsive design
- ✅ Related content

**Weaknesses:**
- ❌ Duplicate metadata (3x)
- ❌ Missing reading time
- ❌ Missing last updated date
- ❌ Missing author bio
- ❌ Missing tags

**Recommendation:**
Implement Phase 1 fixes (critical) to bring structure up to modern blog post standards, especially for healthcare content where credibility and freshness are crucial.

**Priority Actions:**
1. Remove duplicate meta bar
2. Add reading time
3. Add last updated date
4. Add author bio card
5. Add tags support
