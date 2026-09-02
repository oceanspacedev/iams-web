
required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="admin">Administrator (Full Access)</option>
                            <option value="chief">Chief Auditor (Head of Audit)</option>
                            <option value="asmen">Asisten Manager (Asmen)</option>
                            <option value="coordinator">Koordinator Audit</option>
                            <option value="auditor">Auditor Lapangan</option>
                        </select>
                        <div v-if="form.errors.role" class="text-red-600 text-[11px] mt-1">{{ form.errors.role }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Status Akun</label>
                        <select
                            v-model="form.is_active"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option :value="true">Aktif</option>
                            <option :value="false">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.users.index')"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan User' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
