# 🏛️ PREF-DESK - Sistema de Gestão de Chamados (Help Desk)

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![CI Status](https://img.shields.io/github/actions/workflow/status/Francisco-Emanuel/PREF-DESK/ci.yml?style=for-the-badge&label=CI%20Build)

## 📖 Sobre o Projeto

O **PREF-DESK** é uma solução robusta para gerenciamento de suporte técnico e chamados internos. O sistema foi projetado para substituir processos manuais, oferecendo controle de SLA (Acordo de Nível de Serviço), gestão de departamentos e fluxo de aprovação/resolução com assinaturas digitais.

O foco do desenvolvimento foi a **Engenharia de Software Moderna**, utilizando Containerização, Integração Contínua (CI) e Design Patterns para garantir um código limpo e escalável.

---

## 🚀 Tecnologias e Arquitetura

O projeto segue uma arquitetura em camadas (Service Layer) para manter os Controllers magros e a regra de negócio testável.

* **Back-end:** PHP 8.2, Laravel 12
* **Front-end:** Blade, Tailwind CSS, Vite, Alpine.js
* **Banco de Dados:** MySQL 8.0
* **Infraestrutura:** Docker & Docker Compose (Ambiente isolado)
* **Qualidade de Código:** Pest (Testes Automatizados), GitHub Actions (CI)

### 💎 Destaques Técnicos

* **SLA Dinâmico via Enums:** A lógica de cálculo de prazos foi encapsulada em PHP Enums (`PrioridadeSLA`), tornando o código type-safe e desacoplado dos Services.
* **Service Pattern:** Toda a lógica de manipulação de chamados reside em `ChamadoService`, facilitando a manutenção e testes.
* **Developer Experience (DX):** Uso de `Makefile` para abstrair comandos complexos do Docker. O ambiente roda com um único comando.
* **Observabilidade:** Logs estruturados para monitoramento de falhas críticas e violações de SLA via Schedule.
---

## 🛠️ Como Rodar o Projeto

Pré-requisitos: Ter o **Docker** e o **Git** instalados.

### 1. Clone o repositório
```bash
git clone [https://github.com/Francisco-Emanuel/PREF-DESK.git](https://github.com/Francisco-Emanuel/PREF-DESK.git)
cd PREF-DESK

!!🛠️🛠️🛠️🛠️🛠️🛠️🛠️!!