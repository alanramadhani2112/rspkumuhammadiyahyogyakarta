const { registerBlockType } = window.wp.blocks;
const { createElement: el, Fragment } = window.wp.element;
const { InspectorControls, useBlockProps } = window.wp.blockEditor;
const { PanelBody, TextControl, RangeControl } = window.wp.components;
const ServerSideRender = window.wp.serverSideRender?.default || window.wp.serverSideRender;

function fieldControls(attributes, setAttributes) {
  return Object.entries(attributes).map(([name, config]) => {
    if (config?.type === 'string') {
      return el(TextControl, {
        key: name,
        label: name,
        value: config.value ?? '',
        onChange: (value) => setAttributes({ [name]: value }),
      });
    }

    if (config?.type === 'number') {
      return el(RangeControl, {
        key: name,
        label: name,
        value: config.value ?? 0,
        min: 1,
        max: 24,
        onChange: (value) => setAttributes({ [name]: value }),
      });
    }

    return null;
  });
}

function createDynamicBlock(name, title, attributes) {
  registerBlockType(name, {
    apiVersion: 2,
    title,
    icon: 'heart',
    category: 'rspku',
    attributes,
    supports: {
      html: false,
    },
    edit: (props) => {
      const blockProps = useBlockProps({
        className: 'rspku-editor-block',
      });

      const panelFields = Object.entries(attributes)
        .filter(([, config]) => config?.type === 'string' || config?.type === 'number')
        .map(([key, config]) => ({ ...config, value: props.attributes[key], key }));

      return el(
        Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Pengaturan Blok', initialOpen: true },
            fieldControls(
              panelFields.reduce((acc, field) => {
                acc[field.key] = field;
                return acc;
              }, {}),
              props.setAttributes
            )
          )
        ),
        el(
          'div',
          blockProps,
          el(ServerSideRender, {
            block: name,
            attributes: props.attributes,
          })
        )
      );
    },
    save: () => null,
  });
}

createDynamicBlock('rspku/hero-banner', 'RSPKU Hero Banner', {
  eyebrow: { type: 'string', default: '' },
  title: { type: 'string', default: '' },
  description: { type: 'string', default: '' },
  ctaLabel: { type: 'string', default: '' },
  ctaUrl: { type: 'string', default: '' },
  secondaryLabel: { type: 'string', default: '' },
  secondaryUrl: { type: 'string', default: '' },
});

createDynamicBlock('rspku/doctor-search', 'RSPKU Doctor Search', {
  limit: { type: 'number', default: 8 },
});

createDynamicBlock('rspku/doctor-grid', 'RSPKU Doctor Grid', {
  limit: { type: 'number', default: 8 },
  specialization: { type: 'string', default: '' },
});

createDynamicBlock('rspku/service-cards', 'RSPKU Service Cards', {
  limit: { type: 'number', default: 8 },
});

createDynamicBlock('rspku/faq', 'RSPKU FAQ', {
  items: { type: 'array', default: [] },
});

createDynamicBlock('rspku/cta-banner', 'RSPKU CTA Banner', {
  title: { type: 'string', default: '' },
  description: { type: 'string', default: '' },
  ctaLabel: { type: 'string', default: '' },
  ctaUrl: { type: 'string', default: '' },
});

createDynamicBlock('rspku/insurance-partners', 'RSPKU Insurance Partners', {
  items: { type: 'array', default: [] },
});

createDynamicBlock('rspku/journal-list', 'RSPKU Journal List', {
  limit: { type: 'number', default: 6 },
});

createDynamicBlock('rspku/article-list', 'RSPKU Article List', {
  limit: { type: 'number', default: 6 },
});
