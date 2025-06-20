class TaskManager {
  constructor() {
    this.selectors = {
      form: "#task-form",
      list: "#task-list",
      task: ".task",
      toggleBtn: ".toggle-status",
      deleteBtn: ".delete-task",
      editBtn: ".edit-task",
    };

    this.init();
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    document
      .querySelector(this.selectors.form)
      ?.addEventListener("submit", (e) => {
        e.preventDefault();
        this.addTask(e.target);
      });

    document
      .querySelector(this.selectors.list)
      ?.addEventListener("click", (e) => {
        const taskElement = e.target.closest(this.selectors.task);
        if (!taskElement) return;

        const taskId = taskElement.dataset.id;

        if (e.target.matches(this.selectors.toggleBtn)) {
          this.toggleStatus(taskId, taskElement);
        }

        if (e.target.matches(this.selectors.deleteBtn)) {
          this.deleteTask(taskId, taskElement);
        }

        if (e.target.matches(this.selectors.editBtn)) {
          this.editTask(taskId, taskElement);
        }
      });
  }

  async addTask(form) {
    const formData = new FormData(form);
    const data = {
      title: formData.get("title"),
      description: formData.get("description"),
      due_date: formData.get("due_date"),
    };

    try {
      const response = await this.makeRequest("task_manager_add_task", data);

      if (response.success) {
        form.reset();
        this.refreshTasks();
        this.showNotice("Задача успешно добавлена", "success");
      } else {
        throw new Error(
          response.data?.message || "Ошибка при добавлении задачи"
        );
      }
    } catch (error) {
      console.error("TaskManager error:", error);
      this.showNotice(error.message, "error");
    }
  }

  async editTask(taskId, task) {
    try {
      const response = await this.makeRequest("task_manager_get_task", {
        post_id: taskId,
      });

      if (response.success) {
        this.showEditModal(response.data);
      }
    } catch (error) {
      console.error("TaskManager error:", error);
      this.showNotice("Ошибка при загрузке задачи", "error");
    }
  }

  showEditModal(task) {
    this.closeModal?.();

    const modal = document.createElement("div");
    modal.className = "tm-modal";
    modal.innerHTML = `
      <div class="tm-modal-content">
        <h3>Редактировать задачу</h3>
        <form id="edit-task-form" class="task-form">
          <div class="form-group">
            <label>Название:</label>
            <input type="text" name="title" value="${task.title}" required>
          </div>
          <div class="form-group">
            <label>Описание:</label>
            <textarea name="description" rows="3">${task.description}</textarea>
          </div>
          <div class="form-group">
            <label>Дата и время:</label>
            <input type="datetime-local" name="due_date" value="${task.due_date}" required>
          </div>
          <div class="form-actions">
            <button type="button" class="button tm-cancel-btn">Отмена</button>
            <button type="submit" class="button button-primary">Обновить</button>
          </div>
        </form>
      </div>
    `;

    modal
      .querySelector(".tm-cancel-btn")
      .addEventListener("click", () => modal.remove());

    modal.querySelector("form").addEventListener("submit", async (e) => {
      e.preventDefault();
      await this.updateTask(task.id, new FormData(e.target));
      modal.remove();
    });

    document.body.appendChild(modal);
    this.currentModal = modal;
  }

  async updateTask(taskId, formData) {
    const data = {
      title: formData.get("title"),
      description: formData.get("description"),
      due_date: formData.get("due_date"),
    };

    try {
      const response = await this.makeRequest("task_manager_update_task", {
        post_id: taskId,
        ...data,
      });

      if (response.success) {
        this.refreshTasks();
        this.showNotice("Задача успешно обновлена", "success");
      }
    } catch (error) {
      console.error("TaskManager error:", error);
      this.showNotice("Ошибка при обновлении задачи", "error");
    }
  }

  async toggleStatus(taskId, taskElement) {
    try {
      const response = await this.makeRequest("task_manager_update_status", {
        post_id: taskId,
      });

      if (response.success) {
        taskElement.classList.toggle("completed");
        const button = taskElement.querySelector(this.selectors.toggleBtn);
        if (button) {
          button.textContent =
            response.data.new_status === "completed"
              ? "Вернуть в работу"
              : "Завершить";
        }
      }
    } catch (error) {
      console.error("TaskManager error:", error);
      this.showNotice("Ошибка при изменении статуса", "error");
    }
  }

  async deleteTask(taskId, taskElement) {
    if (!confirm("Вы уверены, что хотите удалить эту задачу?")) {
      return;
    }

    try {
      const response = await this.makeRequest("task_manager_delete_task", {
        post_id: taskId,
      });

      if (response.success) {
        taskElement.remove();
        this.showNotice("Задача удалена", "success");
      }
    } catch (error) {
      console.error("TaskManager error:", error);
      this.showNotice("Ошибка при удалении задачи", "error");
    }
  }

  async refreshTasks() {
    try {
      const response = await this.makeRequest("task_manager_get_tasks");

      if (response.success) {
        const list = document.querySelector(this.selectors.list);
        if (list) {
          location.reload();
        }
      }
    } catch (error) {
      console.error("TaskManager error:", error);
    }
  }

  async makeRequest(action, data = {}) {
    const response = await fetch(taskManager.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action,
        nonce: taskManager.nonce,
        ...data,
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  showNotice(message, type = "success") {
    alert(`${type.toUpperCase()}: ${message}`);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new TaskManager();
});
